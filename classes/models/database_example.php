<?php

namespace local_learning_scorecard\models;

defined('MOODLE_INTERNAL') || die();

/**
 * Example model class demonstrating database table usage
 *
 * This shows how to properly interact with your custom database tables
 * from both local plugins and block plugins.
 *
 * @package    local_learning_scorecard
 * @copyright  2024 Miguel d'Aguiar
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class database_example {

    /**
     * Create a new student XP record
     *
     * @param int $userid The user ID
     * @param int $courseid The course ID
     * @param float $quiz_xp Initial quiz XP
     * @param float $exercise_xp Initial exercise XP
     * @param float $bonus_xp Initial bonus XP
     * @return int The ID of the created record
     */
    public static function create_student_xp_record($userid, $courseid, $quiz_xp = 0, $exercise_xp = 0, $bonus_xp = 0) {
        global $DB;

        $record = new \stdClass();
        $record->userid = $userid;
        $record->courseid = $courseid;
        $record->quiz_xp = $quiz_xp;
        $record->exercise_xp = $exercise_xp;
        $record->bonus_xp = $bonus_xp;
        $record->total_xp = $quiz_xp + $exercise_xp + $bonus_xp;
        $record->level = self::calculate_level($record->total_xp);
        $record->timecreated = time();
        $record->timemodified = time();

        return $DB->insert_record('local_ls_student_xp', $record);
    }

    /**
     * Update student XP record
     *
     * @param int $userid The user ID
     * @param int $courseid The course ID  
     * @param array $updates Array of field => value updates
     * @return bool True on success
     */
    public static function update_student_xp($userid, $courseid, $updates) {
        global $DB;

        $record = $DB->get_record('local_ls_student_xp', [
            'userid' => $userid,
            'courseid' => $courseid
        ]);

        if (!$record) {
            return false;
        }

        foreach ($updates as $field => $value) {
            if (property_exists($record, $field)) {
                $record->$field = $value;
            }
        }

        // Recalculate total XP and level
        $record->total_xp = $record->quiz_xp + $record->exercise_xp + $record->bonus_xp;
        $record->level = self::calculate_level($record->total_xp);
        $record->timemodified = time();

        return $DB->update_record('local_ls_student_xp', $record);
    }

    /**
     * Get student XP record
     *
     * @param int $userid The user ID
     * @param int $courseid The course ID
     * @return object|false The record or false if not found
     */
    public static function get_student_xp($userid, $courseid) {
        global $DB;

        return $DB->get_record('local_ls_student_xp', [
            'userid' => $userid,
            'courseid' => $courseid
        ]);
    }

    /**
     * Get top students by XP in a course
     *
     * @param int $courseid The course ID
     * @param int $limit Number of records to return
     * @return array Array of student XP records
     */
    public static function get_top_students($courseid, $limit = 10) {
        global $DB;

        $sql = "SELECT lsx.*, u.firstname, u.lastname 
                FROM {local_ls_student_xp} lsx
                JOIN {user} u ON lsx.userid = u.id
                WHERE lsx.courseid = ?
                ORDER BY lsx.total_xp DESC, lsx.timemodified ASC";

        return $DB->get_records_sql($sql, [$courseid], 0, $limit);
    }

    /**
     * Add XP history entry
     *
     * @param int $userid The user ID
     * @param int $courseid The course ID
     * @param string $activity_type Type of activity
     * @param int $activity_id ID of the activity (optional)
     * @param float $xp_earned XP amount earned
     * @param string $reason Reason for XP award
     * @return int The ID of the created record
     */
    public static function add_xp_history($userid, $courseid, $activity_type, $activity_id, $xp_earned, $reason = '') {
        global $DB;

        $record = new \stdClass();
        $record->userid = $userid;
        $record->courseid = $courseid;
        $record->activity_type = $activity_type;
        $record->activity_id = $activity_id;
        $record->xp_earned = $xp_earned;
        $record->reason = $reason;
        $record->timecreated = time();

        return $DB->insert_record('local_ls_xp_history', $record);
    }

    /**
     * Get XP history for a student
     *
     * @param int $userid The user ID
     * @param int $courseid The course ID
     * @param int $limit Number of records to return
     * @return array Array of XP history records
     */
    public static function get_student_xp_history($userid, $courseid, $limit = 50) {
        global $DB;

        return $DB->get_records('local_ls_xp_history', [
            'userid' => $userid,
            'courseid' => $courseid
        ], 'timecreated DESC', '*', 0, $limit);
    }

    /**
     * Get or create XP settings for a course
     *
     * @param int $courseid The course ID
     * @return object The settings record
     */
    public static function get_course_xp_settings($courseid) {
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

    /**
     * Update XP settings for a course
     *
     * @param int $courseid The course ID
     * @param array $updates Array of field => value updates
     * @return bool True on success
     */
    public static function update_course_xp_settings($courseid, $updates) {
        global $DB;

        $settings = self::get_course_xp_settings($courseid);

        foreach ($updates as $field => $value) {
            if (property_exists($settings, $field) && $field !== 'id') {
                $settings->$field = $value;
            }
        }

        $settings->timemodified = time();

        return $DB->update_record('local_ls_xp_settings', $settings);
    }

    /**
     * Calculate level based on total XP
     *
     * @param float $total_xp Total XP amount
     * @return int The calculated level
     */
    private static function calculate_level($total_xp) {
        // Example level calculation - adjust as needed
        // Level 1: 0-99 XP, Level 2: 100-299 XP, Level 3: 300-599 XP, etc.
        if ($total_xp < 100) return 1;
        if ($total_xp < 300) return 2;
        if ($total_xp < 600) return 3;
        if ($total_xp < 1000) return 4;
        
        // For higher levels, use a formula
        return floor(($total_xp - 1000) / 500) + 5;
    }

    /**
     * Execute a transaction safely
     *
     * @param callable $callback Function to execute within transaction
     * @return mixed The result of the callback
     * @throws \Exception If transaction fails
     */
    public static function execute_transaction($callback) {
        global $DB;

        $transaction = $DB->start_delegated_transaction();

        try {
            $result = $callback();
            $DB->commit_delegated_transaction($transaction);
            return $result;
        } catch (\Exception $e) {
            $DB->rollback_delegated_transaction($transaction, $e);
            throw $e;
        }
    }

    /**
     * Example of complex database operation with joins
     *
     * @param int $courseid The course ID
     * @return array Statistics about course XP
     */
    public static function get_course_xp_statistics($courseid) {
        global $DB;

        $sql = "
            SELECT 
                COUNT(*) as total_students,
                AVG(total_xp) as avg_xp,
                MAX(total_xp) as max_xp,
                MIN(total_xp) as min_xp,
                SUM(CASE WHEN total_xp > 100 THEN 1 ELSE 0 END) as students_over_100xp,
                SUM(quiz_xp + exercise_xp + bonus_xp) as course_total_xp
            FROM {local_ls_student_xp} 
            WHERE courseid = ?
        ";

        return $DB->get_record_sql($sql, [$courseid]);
    }
}