<?php
namespace local_learning_scorecard\models;

use local_learning_scorecard\helpers\xp_calculator;

defined('MOODLE_INTERNAL') || die();

class quiz_xp {
    
    /**
     * Calculate XP from quizzes for a user
     */
    public static function get_quiz_xp_data($userid, $courseid) {
        global $DB;
        
        $sql = "SELECT COUNT(DISTINCT qa.quiz) as count, AVG(qa.sumgrades) as avg_grade
                FROM {quiz_attempts} qa
                JOIN {quiz} q ON qa.quiz = q.id
                WHERE qa.userid = ? AND q.course = ? AND qa.state = 'finished'";
        
        $result = $DB->get_record_sql($sql, [$userid, $courseid]);
        
        $count = $result ? $result->count : 0;
        $avg_grade = $result && $result->avg_grade ? $result->avg_grade : 0;
        
        // Get XP configuration
        $config = xp_settings::get_course_settings($courseid);
        
        // Calculate XP using helper
        $total_xp = xp_calculator::calculate_base_with_grade_bonus(
            $count, 
            $config['quiz_base_xp'], 
            $avg_grade, 
            $config['grade_multiplier']
        );
        
        return [
            'count' => $count,
            'xp' => xp_calculator::round_xp($total_xp),
            'avg_grade' => $avg_grade
        ];
    }
    
    /**
     * Get quiz statistics for a user
     */
    public static function get_quiz_stats($userid, $courseid) {
        global $DB;
        
        $sql = "SELECT 
                    COUNT(DISTINCT qa.quiz) as total_quizzes,
                    COUNT(qa.id) as total_attempts,
                    AVG(qa.sumgrades) as avg_grade,
                    MAX(qa.sumgrades) as best_grade,
                    MIN(qa.sumgrades) as worst_grade
                FROM {quiz_attempts} qa
                JOIN {quiz} q ON qa.quiz = q.id
                WHERE qa.userid = ? AND q.course = ? AND qa.state = 'finished'";
        
        $result = $DB->get_record_sql($sql, [$userid, $courseid]);
        
        return [
            'total_quizzes' => $result ? $result->total_quizzes : 0,
            'total_attempts' => $result ? $result->total_attempts : 0,
            'avg_grade' => $result && $result->avg_grade ? round($result->avg_grade, 2) : 0,
            'best_grade' => $result && $result->best_grade ? round($result->best_grade, 2) : 0,
            'worst_grade' => $result && $result->worst_grade ? round($result->worst_grade, 2) : 0
        ];
    }
    
    /**
     * Get all quiz attempts for a user in a course
     */
    public static function get_quiz_attempts($userid, $courseid) {
        global $DB;
        
        $sql = "SELECT qa.*, q.name as quiz_name
                FROM {quiz_attempts} qa
                JOIN {quiz} q ON qa.quiz = q.id
                WHERE qa.userid = ? AND q.course = ? AND qa.state = 'finished'
                ORDER BY qa.timefinish DESC";
        
        return $DB->get_records_sql($sql, [$userid, $courseid]);
    }
}
?>
