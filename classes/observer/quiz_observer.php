<?php
/**
 * Quiz event observer for Learning Scorecard
 *
 * @package    local_learning_scorecard
 * @copyright  2024 Miguel d'Aguiar
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_learning_scorecard\observer;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/quiz/locallib.php');

/**
 * Quiz observer class
 */
class quiz_observer {

    /**
     * Handle quiz attempt submitted event
     *
     * @param \mod_quiz\event\attempt_submitted $event
     */
    public static function attempt_submitted(\mod_quiz\event\attempt_submitted $event) {
        global $DB;

        try {
            $userid = $event->userid;
            $courseid = $event->courseid;
            $quizid = $event->objectid;

            // Get the quiz attempt to calculate XP
            $attempt = $DB->get_record('quiz_attempts', ['id' => $event->other['quizid']]);
            if (!$attempt) {
                return;
            }

            // Get quiz details
            $quiz = $DB->get_record('quiz', ['id' => $attempt->quiz]);
            if (!$quiz) {
                return;
            }

            // Calculate XP based on score
            $xp_earned = self::calculate_quiz_xp($attempt, $quiz, $courseid);

            // Update student XP record
            self::update_student_xp($userid, $courseid, $xp_earned, 'quiz', $quiz->id, 
                'Quiz completed: ' . $quiz->name);

        } catch (\Exception $e) {
            // Log error but don't break the quiz completion process
            error_log('Learning Scorecard Quiz Observer Error: ' . $e->getMessage());
        }
    }

    /**
     * Calculate XP earned from a quiz attempt
     *
     * @param object $attempt Quiz attempt record
     * @param object $quiz Quiz record
     * @param int $courseid Course ID
     * @return float XP earned
     */
    private static function calculate_quiz_xp($attempt, $quiz, $courseid) {
        // Get course XP settings
        $settings = self::get_course_settings($courseid);
        
        // Base XP for completing the quiz
        $base_xp = $settings->quiz_base_xp;
        
        // Calculate score percentage
        $score_percentage = 0;
        if ($quiz->sumgrades > 0) {
            $score_percentage = ($attempt->sumgrades / $quiz->sumgrades) * 100;
        }
        
        // XP calculation formula:
        // Base XP + (Score percentage * multiplier)
        $xp_earned = $base_xp + ($score_percentage * ($base_xp / 100));
        
        // Bonus for perfect score
        if ($score_percentage >= 100) {
            $xp_earned *= $settings->perfect_score_multiplier;
        }
        
        return round($xp_earned, 2);
    }

    /**
     * Update student XP record safely
     *
     * @param int $userid User ID
     * @param int $courseid Course ID
     * @param float $quiz_xp_earned XP earned from quiz
     * @param string $activity_type Activity type for history
     * @param int $activity_id Activity ID for history
     * @param string $reason Reason for XP award
     */
    private static function update_student_xp($userid, $courseid, $quiz_xp_earned, $activity_type, $activity_id, $reason) {
        global $DB;

        // Start transaction for data consistency
        $transaction = $DB->start_delegated_transaction();

        try {
            // Get or create student XP record
            $xp_record = $DB->get_record('local_ls_student_xp', [
                'userid' => $userid,
                'courseid' => $courseid
            ]);

            if (!$xp_record) {
                // Create new record starting at 0
                $xp_record = new \stdClass();
                $xp_record->userid = $userid;
                $xp_record->courseid = $courseid;
                $xp_record->quiz_xp = 0;
                $xp_record->exercise_xp = 0;
                $xp_record->bonus_xp = 0;
                $xp_record->total_xp = 0;
                $xp_record->rank = 'Newbie'; // Start with Newbie rank
                $xp_record->timecreated = time();
                $xp_record->timemodified = time();

                $xp_record->id = $DB->insert_record('local_ls_student_xp', $xp_record);
            }

            // Add the earned XP to quiz XP
            $xp_record->quiz_xp += $quiz_xp_earned;
            
            // Recalculate totals
            $xp_record->total_xp = $xp_record->quiz_xp + $xp_record->exercise_xp + $xp_record->bonus_xp;
            $xp_record->rank = self::calculate_rank($xp_record->total_xp);
            $xp_record->timemodified = time();

            // Update the record
            $DB->update_record('local_ls_student_xp', $xp_record);

            // Add history entry
            $history_record = new \stdClass();
            $history_record->userid = $userid;
            $history_record->courseid = $courseid;
            $history_record->activity_type = $activity_type;
            $history_record->activity_id = $activity_id;
            $history_record->xp_earned = $quiz_xp_earned;
            $history_record->reason = $reason;
            $history_record->timecreated = time();

            $DB->insert_record('local_ls_xp_history', $history_record);

            // Commit transaction
            $DB->commit_delegated_transaction($transaction);

        } catch (\Exception $e) {
            $DB->rollback_delegated_transaction($transaction, $e);
            throw $e;
        }
    }

    /**
     * Calculate rank based on total XP
     *
     * @param float $total_xp Total XP amount
     * @return string The rank name
     */
    private static function calculate_rank($total_xp) {
        if ($total_xp < 100) return 'Newbie';
        if ($total_xp < 200) return 'Rookie';
        if ($total_xp < 300) return 'Skilled';
        if ($total_xp < 400) return 'Expert';
        if ($total_xp < 500) return 'Master';
        return 'Legendary';
    }

    /**
     * Get course XP settings
     *
     * @param int $courseid Course ID
     * @return object Settings object
     */
    private static function get_course_settings($courseid) {
        global $DB;

        $settings = $DB->get_record('local_ls_xp_settings', ['courseid' => $courseid]);

        if (!$settings) {
            // Create default settings
            $settings = new \stdClass();
            $settings->courseid = $courseid;
            $settings->quiz_base_xp = 10.00;
            $settings->exercise_base_xp = 5.00;
            $settings->forum_post_xp = 2.00;
            $settings->perfect_score_multiplier = 1.50;
            $settings->enabled = 1;
            $settings->timecreated = time();
            $settings->timemodified = time();

            $settings->id = $DB->insert_record('local_ls_xp_settings', $settings);
        }

        return $settings;
    }
}