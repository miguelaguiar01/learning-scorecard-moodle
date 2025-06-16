<?php
namespace local_learning_scorecard;

defined('MOODLE_INTERNAL') || die();

class leaderboard_manager {
    
    // XP Configuration - easily modifiable for future development
    const QUIZ_BASE_XP = 100;
    const EXERCISE_BASE_XP = 50;
    const FORUM_POST_XP = 10;
    const GRADE_MULTIPLIER = 2;
    
    /**
     * Get overall leaderboard with all XP types
     */
    public static function get_all_leaderboard($courseid) {
        global $DB;
        
        $context = \context_course::instance($courseid);
        $students = get_enrolled_users($context, 'mod/quiz:attempt');
        
        $leaderboard = [];
        
        foreach ($students as $student) {
            $quiz_data = self::get_quiz_xp_data($student->id, $courseid);
            $exercise_data = self::get_exercise_xp_data($student->id, $courseid);
            $bonus_xp = self::get_bonus_xp($student->id, $courseid);
            
            $data = [
                'userid' => $student->id,
                'fullname' => fullname($student),
                'quiz_xp' => $quiz_data['xp'],
                'exercise_xp' => $exercise_data['xp'],
                'bonus_xp' => $bonus_xp,
                'total_xp' => $quiz_data['xp'] + $exercise_data['xp'] + $bonus_xp
            ];
            
            $leaderboard[] = $data;
        }
        
        // Sort by total XP
        usort($leaderboard, function($a, $b) {
            return $b['total_xp'] - $a['total_xp'];
        });
        
        return $leaderboard;
    }
    
    /**
     * Get quiz-focused leaderboard
     */
    public static function get_quiz_leaderboard($courseid) {
        global $DB;
        
        $context = \context_course::instance($courseid);
        $students = get_enrolled_users($context, 'mod/quiz:attempt');
        
        $leaderboard = [];
        
        foreach ($students as $student) {
            $quiz_data = self::get_quiz_xp_data($student->id, $courseid);
            
            $data = [
                'userid' => $student->id,
                'fullname' => fullname($student),
                'quizzes_completed' => $quiz_data['count'],
                'quiz_xp' => $quiz_data['xp']
            ];
            
            $leaderboard[] = $data;
        }
        
        // Sort by quiz XP
        usort($leaderboard, function($a, $b) {
            return $b['quiz_xp'] - $a['quiz_xp'];
        });
        
        return $leaderboard;
    }
    
    /**
     * Get exercise-focused leaderboard
     */
    public static function get_exercise_leaderboard($courseid) {
        global $DB;
        
        $context = \context_course::instance($courseid);
        $students = get_enrolled_users($context, 'mod/quiz:attempt');
        
        $leaderboard = [];
        
        foreach ($students as $student) {
            $exercise_data = self::get_exercise_xp_data($student->id, $courseid);
            
            $data = [
                'userid' => $student->id,
                'fullname' => fullname($student),
                'exercises_completed' => $exercise_data['count'],
                'exercise_xp' => $exercise_data['xp']
            ];
            
            $leaderboard[] = $data;
        }
        
        // Sort by exercise XP
        usort($leaderboard, function($a, $b) {
            return $b['exercise_xp'] - $a['exercise_xp'];
        });
        
        return $leaderboard;
    }
    
    /**
     * Calculate XP from quizzes
     */
    private static function get_quiz_xp_data($userid, $courseid) {
        global $DB;
        
        $sql = "SELECT COUNT(DISTINCT qa.quiz) as count, AVG(qa.sumgrades) as avg_grade
                FROM {quiz_attempts} qa
                JOIN {quiz} q ON qa.quiz = q.id
                WHERE qa.userid = ? AND q.course = ? AND qa.state = 'finished'";
        
        $result = $DB->get_record_sql($sql, [$userid, $courseid]);
        
        $count = $result ? $result->count : 0;
        $avg_grade = $result && $result->avg_grade ? $result->avg_grade : 0;
        
        // XP Calculation: Base XP per quiz + grade bonus
        $base_xp = $count * self::QUIZ_BASE_XP;
        $grade_bonus = $avg_grade * self::GRADE_MULTIPLIER;
        $total_xp = $base_xp + $grade_bonus;
        
        return [
            'count' => $count,
            'xp' => round($total_xp)
        ];
    }
    
    /**
     * Calculate XP from exercises (assignments, workshops, etc.)
     */
    private static function get_exercise_xp_data($userid, $courseid) {
        global $DB;
        
        // Get assignments
        $assignment_sql = "SELECT COUNT(*) as count, AVG(g.grade) as avg_grade
                          FROM {assign_submission} s
                          JOIN {assign} a ON s.assignment = a.id
                          LEFT JOIN {assign_grades} g ON g.assignment = a.id AND g.userid = s.userid
                          WHERE s.userid = ? AND a.course = ? AND s.status = 'submitted'";
        
        $assignment_result = $DB->get_record_sql($assignment_sql, [$userid, $courseid]);
        
        // Get workshop submissions (if workshop module exists)
        $workshop_count = 0;
        $workshop_grade = 0;
        
        if ($DB->get_manager()->table_exists('workshop')) {
            $workshop_sql = "SELECT COUNT(*) as count, AVG(ws.grade) as avg_grade
                            FROM {workshop_submissions} ws
                            JOIN {workshop} w ON ws.workshopid = w.id
                            WHERE ws.authorid = ? AND w.course = ?";
            
            $workshop_result = $DB->get_record_sql($workshop_sql, [$userid, $courseid]);
            $workshop_count = $workshop_result ? $workshop_result->count : 0;
            $workshop_grade = $workshop_result && $workshop_result->avg_grade ? $workshop_result->avg_grade : 0;
        }
        
        $assignment_count = $assignment_result ? $assignment_result->count : 0;
        $assignment_grade = $assignment_result && $assignment_result->avg_grade ? $assignment_result->avg_grade : 0;
        
        $total_count = $assignment_count + $workshop_count;
        $avg_grade = $total_count > 0 ? (($assignment_grade * $assignment_count) + ($workshop_grade * $workshop_count)) / $total_count : 0;
        
        // XP Calculation: Base XP per exercise + grade bonus
        $base_xp = $total_count * self::EXERCISE_BASE_XP;
        $grade_bonus = $avg_grade * self::GRADE_MULTIPLIER;
        $total_xp = $base_xp + $grade_bonus;
        
        return [
            'count' => $total_count,
            'xp' => round($total_xp)
        ];
    }
    
    /**
     * Calculate bonus XP (forum participation, etc.)
     * Space for future development
     */
    private static function get_bonus_xp($userid, $courseid) {
        global $DB;
        
        // Forum posts XP
        $forum_sql = "SELECT COUNT(*) as count
                      FROM {forum_posts} fp
                      JOIN {forum_discussions} fd ON fp.discussion = fd.id
                      JOIN {forum} f ON fd.forum = f.id
                      WHERE fp.userid = ? AND f.course = ?";
        
        $forum_result = $DB->get_record_sql($forum_sql, [$userid, $courseid]);
        $forum_posts = $forum_result ? $forum_result->count : 0;
        
        $forum_xp = $forum_posts * self::FORUM_POST_XP;
        
        // Space for additional bonus XP calculations:
        // - Course completion bonuses
        // - Perfect attendance bonuses
        // - Peer review bonuses
        // - Early submission bonuses
        // - Streak bonuses
        
        return $forum_xp;
    }
    
    /**
     * Get XP configuration - for future admin interface
     */
    public static function get_xp_config() {
        return [
            'quiz_base_xp' => self::QUIZ_BASE_XP,
            'exercise_base_xp' => self::EXERCISE_BASE_XP,
            'forum_post_xp' => self::FORUM_POST_XP,
            'grade_multiplier' => self::GRADE_MULTIPLIER
        ];
    }
    
    /**
     * Set XP configuration - for future admin interface
     */
    // TODO Setup XP Config
    public static function set_xp_config($config) {
        // This would typically use Moodle's config storage
        // For now, constants are used for simplicity
        // Future: Store in mdl_config_plugins table
    }
}
?>