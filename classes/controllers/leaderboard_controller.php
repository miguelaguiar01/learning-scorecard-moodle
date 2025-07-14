<?php
namespace local_learning_scorecard\controllers;

use local_learning_scorecard\models\student_xp;
use local_learning_scorecard\models\quiz_xp;
use local_learning_scorecard\models\exercise_xp;
use local_learning_scorecard\models\guild_xp;

defined('MOODLE_INTERNAL') || die();

class leaderboard_controller {
    
    /**
     * Get overall leaderboard with all XP types
     */
    public static function get_all_leaderboard($courseid) {
        $students_xp = student_xp::get_all_students_xp($courseid);
        
        // Sort by total XP (descending)
        usort($students_xp, function($a, $b) {
            return $b['total_xp'] - $a['total_xp'];
        });
        
        return $students_xp;
    }
    
    /**
     * Get quiz-focused leaderboard
     */
    public static function get_quiz_leaderboard($courseid) {
        $context = \context_course::instance($courseid);
        $students = get_enrolled_users($context, 'mod/quiz:attempt');
        
        $leaderboard = [];
        
        foreach ($students as $student) {
            $quiz_data = quiz_xp::get_quiz_xp_data($student->id, $courseid);
            
            $data = [
                'userid' => $student->id,
                'fullname' => fullname($student),
                'quizzes_completed' => $quiz_data['count'],
                'quiz_xp' => $quiz_data['xp']
            ];
            
            $leaderboard[] = $data;
        }
        
        // Sort by quiz XP (descending)
        usort($leaderboard, function($a, $b) {
            return $b['quiz_xp'] - $a['quiz_xp'];
        });
        
        return $leaderboard;
    }
    
    /**
     * Get exercise-focused leaderboard
     */
    public static function get_exercise_leaderboard($courseid) {
        $context = \context_course::instance($courseid);
        $students = get_enrolled_users($context, 'mod/quiz:attempt');
        
        $leaderboard = [];
        
        foreach ($students as $student) {
            $exercise_data = exercise_xp::get_exercise_xp_data($student->id, $courseid);
            
            $data = [
                'userid' => $student->id,
                'fullname' => fullname($student),
                'exercises_completed' => $exercise_data['count'],
                'exercise_xp' => $exercise_data['xp']
            ];
            
            $leaderboard[] = $data;
        }
        
        // Sort by exercise XP (descending)
        usort($leaderboard, function($a, $b) {
            return $b['exercise_xp'] - $a['exercise_xp'];
        });
        
        return $leaderboard;
    }
    
    /**
     * Get guild-focused leaderboard
     */
    public static function get_guild_leaderboard($courseid) {
        return guild_xp::get_guild_leaderboard($courseid);
    }
    
    /**
     * Get combined leaderboard (students ranked by sum of positions across all leaderboards)
     */
    public static function get_combined_leaderboard($courseid) {
        // Get all three leaderboards
        $all_leaderboard = self::get_all_leaderboard($courseid);
        $quiz_leaderboard = self::get_quiz_leaderboard($courseid);
        $exercise_leaderboard = self::get_exercise_leaderboard($courseid);
        
        // Create position maps for each leaderboard
        $all_positions = [];
        $quiz_positions = [];
        $exercise_positions = [];
        
        // Map positions for Overall leaderboard
        foreach ($all_leaderboard as $position => $student) {
            $all_positions[$student['userid']] = $position + 1; // +1 because array is 0-indexed
        }
        
        // Map positions for Quiz leaderboard
        foreach ($quiz_leaderboard as $position => $student) {
            $quiz_positions[$student['userid']] = $position + 1;
        }
        
        // Map positions for Exercise leaderboard
        foreach ($exercise_leaderboard as $position => $student) {
            $exercise_positions[$student['userid']] = $position + 1;
        }
        
        // Get all students who appear in at least one leaderboard
        $all_userids = array_unique(array_merge(
            array_keys($all_positions),
            array_keys($quiz_positions), 
            array_keys($exercise_positions)
        ));
        
        $combined_leaderboard = [];
        
        foreach ($all_userids as $userid) {
            global $DB;
            // Get user info
            $user = $DB->get_record('user', ['id' => $userid]);
            if (!$user) continue;
            
            // Get positions (use high number if not present in a leaderboard)
            $max_position = max(count($all_leaderboard), count($quiz_leaderboard), count($exercise_leaderboard)) + 1;
            
            $all_pos = isset($all_positions[$userid]) ? $all_positions[$userid] : $max_position;
            $quiz_pos = isset($quiz_positions[$userid]) ? $quiz_positions[$userid] : $max_position;
            $exercise_pos = isset($exercise_positions[$userid]) ? $exercise_positions[$userid] : $max_position;
            
            // Calculate combined score (sum of positions - lower is better)
            $combined_score = $all_pos + $quiz_pos + $exercise_pos;
            
            $student_data = [
                'userid' => $userid,
                'fullname' => fullname($user),
                'all_position' => $all_pos,
                'quiz_position' => $quiz_pos,
                'exercise_position' => $exercise_pos,
                'combined_score' => $combined_score
            ];
            
            $combined_leaderboard[] = $student_data;
        }
        
        // Sort by combined score (ascending - lower score is better)
        usort($combined_leaderboard, function($a, $b) {
            return $a['combined_score'] - $b['combined_score'];
        });
        
        return $combined_leaderboard;
    }
    
    /**
     * Get leaderboard by type
     */
    public static function get_leaderboard_by_type($courseid, $type) {
        switch ($type) {
            case 'all':
                return self::get_all_leaderboard($courseid);
            case 'quiz':
                return self::get_quiz_leaderboard($courseid);
            case 'exercise':
                return self::get_exercise_leaderboard($courseid);
            case 'guild':
                return self::get_guild_leaderboard($courseid);
            case 'combined':
                return self::get_combined_leaderboard($courseid);
            default:
                return self::get_all_leaderboard($courseid);
        }
    }
    
    /**
     * Get student's position in a specific leaderboard
     */
    public static function get_student_position($userid, $courseid, $type) {
        $leaderboard = self::get_leaderboard_by_type($courseid, $type);
        
        foreach ($leaderboard as $position => $student) {
            if ($student['userid'] == $userid) {
                return $position + 1; // +1 because array is 0-indexed
            }
        }
        
        return null; // Student not found in leaderboard
    }
    
    /**
     * Get student's positions in all leaderboards
     */
    public static function get_student_all_positions($userid, $courseid) {
        return [
            'all' => self::get_student_position($userid, $courseid, 'all'),
            'quiz' => self::get_student_position($userid, $courseid, 'quiz'),
            'exercise' => self::get_student_position($userid, $courseid, 'exercise'),
            'combined' => self::get_student_position($userid, $courseid, 'combined')
        ];
    }
    
    /**
     * Get leaderboard statistics
     */
    public static function get_leaderboard_stats($courseid) {
        $context = \context_course::instance($courseid);
        $students = get_enrolled_users($context, 'mod/quiz:attempt');
        
        $total_students = count($students);
        $active_students = 0;
        $total_xp = 0;
        
        foreach ($students as $student) {
            $xp_data = student_xp::get_student_total_xp($student->id, $courseid);
            if ($xp_data['total_xp'] > 0) {
                $active_students++;
            }
            $total_xp += $xp_data['total_xp'];
        }
        
        $avg_xp = $total_students > 0 ? round($total_xp / $total_students) : 0;
        
        return [
            'total_students' => $total_students,
            'active_students' => $active_students,
            'total_xp' => $total_xp,
            'avg_xp' => $avg_xp,
            'participation_rate' => $total_students > 0 ? round(($active_students / $total_students) * 100, 1) : 0
        ];
    }
}
?>
