<?php
namespace local_learning_scorecard\helpers;

defined('MOODLE_INTERNAL') || die();

class grade_processor {
    
    /**
     * Check if a grade is valid and released
     */
    public static function is_grade_valid_and_released($grade_record) {
        return $grade_record->finalgrade !== null && 
               $grade_record->hidden == 0 && 
               ($grade_record->locked > 0 || $grade_record->overridden > 0 || $grade_record->finalgrade !== null);
    }
    
    /**
     * Process assignment grades and calculate statistics
     */
    public static function process_assignment_grades($assignments) {
        $count = count($assignments);
        $total_grade = 0;
        
        foreach ($assignments as $assignment) {
            if ($assignment->grademax > 0) {
                $grade_percent = ($assignment->finalgrade / $assignment->grademax) * 100;
                $total_grade += $grade_percent;
            }
        }
        
        $avg_grade = $count > 0 ? $total_grade / $count : 0;
        
        return [
            'count' => $count,
            'total_grade' => $total_grade,
            'avg_grade' => $avg_grade
        ];
    }
    
    /**
     * Process workshop grades and calculate statistics
     */
    public static function process_workshop_grades($workshops) {
        $count = count($workshops);
        $total_grade = 0;
        
        foreach ($workshops as $workshop) {
            if ($workshop->grademax > 0) {
                $grade_percent = ($workshop->finalgrade / $workshop->grademax) * 100;
                $total_grade += $grade_percent;
            }
        }
        
        $avg_grade = $count > 0 ? $total_grade / $count : 0;
        
        return [
            'count' => $count,
            'total_grade' => $total_grade,
            'avg_grade' => $avg_grade
        ];
    }
    
    /**
     * Validate grade bounds
     */
    public static function is_grade_in_bounds($grade, $min = 0, $max = null) {
        if ($grade < $min) {
            return false;
        }
        if ($max !== null && $grade > $max) {
            return false;
        }
        return true;
    }
    
    /**
     * Log grade processing debug information
     */
    public static function log_grade_debug($userid, $courseid, $type, $data) {
        if (debugging()) {
            mtrace("$type Grade Processing for user $userid in course $courseid:");
            foreach ($data as $key => $value) {
                mtrace("- $key: $value");
            }
        }
    }
}
?>
