<?php

namespace local_learning_scorecard\models;

use local_learning_scorecard\helpers\grade_processor;
use local_learning_scorecard\helpers\xp_calculator;

defined('MOODLE_INTERNAL') || die();

class exercise_xp
{

    /**
     * Calculate XP from exercises (assignments, workshops, etc.)
     */
    public static function get_exercise_xp_data($userid, $courseId): array
    {

        $assignments = self::get_graded_assignments($userid, $courseId);
        $workshops = self::get_graded_workshops($userid, $courseId);

        $assignment_data = grade_processor::process_assignment_grades($assignments);
        $workshop_data = grade_processor::process_workshop_grades($workshops);

        $total_count = $assignment_data['count'] + $workshop_data['count'];

        if ($total_count == 0) {
            return [
                'count' => 0,
                'xp' => 0,
                'pending' => self::get_pending_submissions_count($userid, $courseId)
            ];
        }

        // Calculate weighted average grade
        $total_avg_grade = xp_calculator::calculate_weighted_average([
            [
                'avg_grade' => $assignment_data['avg_grade'],
                'count' => $assignment_data['count']
            ],
            [
                'avg_grade' => $workshop_data['avg_grade'],
                'count' => $workshop_data['count']
            ]
        ]);

        // Get XP configuration
        $config = xp_settings::get_course_settings($courseId);

        // Calculate XP
        $total_xp = xp_calculator::calculate_exercise_xp(
            $total_count,
            $config['exercise_base_xp'],
            $total_avg_grade,
            $config['grade_multiplier']
        );

        // Debug logging
        grade_processor::log_grade_debug($userid, $courseId, 'Exercise', [
            'graded_assignments' => $assignment_data['count'],
            'assignment_avg' => round($assignment_data['avg_grade'], 2) . '%',
            'graded_workshops' => $workshop_data['count'],
            'workshop_avg' => round($workshop_data['avg_grade'], 2) . '%',
            'total_xp' => xp_calculator::round_xp($total_xp)
        ]);

        return [
            'count' => $total_count,
            'xp' => xp_calculator::round_xp($total_xp),
            'avg_grade' => round($total_avg_grade, 2),
            'details' => [
                'assignments' => $assignment_data['count'],
                'workshops' => $workshop_data['count'],
                'assignment_avg' => round($assignment_data['avg_grade'], 2),
                'workshop_avg' => round($workshop_data['avg_grade'], 2)
            ]
        ];
    }

    /**
     * Get graded assignments for a user
     */
    private static function get_graded_assignments($userid, $courseId)
    {
        global $DB;


        $sql = "SELECT 
            a.id as assignment_id,
            a.name,
            a.grade as max_grade,
            g.grade as user_grade,
            gi.grademax,
            gg.finalgrade,
            gg.hidden,
            gg.locked,
            gg.overridden
        FROM mdl_assign a
        JOIN mdl_assign_submission s ON s.assignment = a.id
        JOIN mdl_assign_grades g ON g.assignment = a.id AND g.userid = s.userid
        JOIN mdl_grade_items gi ON gi.iteminstance = a.id 
            AND gi.itemmodule = 'assign' 
            AND gi.courseid = a.course
            AND gi.itemtype = 'mod'
        JOIN mdl_grade_grades gg ON gg.itemid = gi.id AND gg.userid = s.userid
        WHERE 
            s.userid = ? 
            AND a.course = ? 
            AND s.status = 'submitted'
            AND s.latest = 1
            AND g.grade IS NOT NULL 
            AND g.grade >= 0
            AND gg.finalgrade IS NOT NULL
            AND gg.hidden = 0
            AND (gg.locked > 0 OR gg.overridden > 0 OR gg.finalgrade IS NOT NULL)";

        return $DB->get_records_sql($sql, [$userid, $courseId]);
    }

    /**
     * Get graded workshops for a user
     */
    private static function get_graded_workshops($userid, $courseId)
    {
        global $DB;

        if (!$DB->get_manager()->table_exists('workshop_submissions')) {
            return [];
        }

        $sql = "SELECT 
            ws.id,
            w.name,
            w.grade as max_grade,
            ws.grade as user_grade,
            gi.grademax,
            gg.finalgrade,
            gg.hidden
        FROM mdl_workshop_submissions ws
        JOIN mdl_workshop w ON ws.workshopid = w.id
        JOIN mdl_grade_items gi ON gi.iteminstance = w.id 
            AND gi.itemmodule = 'workshop' 
            AND gi.courseid = w.course
            AND gi.itemtype = 'mod'
        JOIN mdl_grade_grades gg ON gg.itemid = gi.id AND gg.userid = ws.authorid
        WHERE 
            ws.authorid = ? 
            AND w.course = ?
            AND ws.grade IS NOT NULL
            AND ws.grade >= 0
            AND gg.finalgrade IS NOT NULL
            AND gg.hidden = 0
            AND ws.published = 1";

        return $DB->get_records_sql($sql, [$userid, $courseId]);
    }

    /**
     * Get count of pending (ungraded) submissions
     */
    public static function get_pending_submissions_count($userid, $courseId)
    {
        global $DB;

        $sql = "SELECT COUNT(DISTINCT a.id) as count
               FROM mdl_assign a
               JOIN mdl_assign_submission s ON s.assignment = a.id
               LEFT JOIN mdl_assign_grades g ON g.assignment = a.id AND g.userid = s.userid
               WHERE 
                   s.userid = ? 
                   AND a.course = ? 
                   AND s.status = 'submitted'
                   AND s.latest = 1
                   AND (g.grade IS NULL OR g.grade < 0)";

        $result = $DB->get_record_sql($sql, [$userid, $courseId]);

        return $result ? $result->count : 0;
    }

    /**
     * Get exercise statistics for a user
     */
    public static function get_exercise_stats($userid, $courseId): array
    {
        $assignments = self::get_graded_assignments($userid, $courseId);
        $workshops = self::get_graded_workshops($userid, $courseId);
        $pending = self::get_pending_submissions_count($userid, $courseId);

        return [
            'total_graded' => count($assignments) + count($workshops),
            'assignments' => count($assignments),
            'workshops' => count($workshops),
            'pending' => $pending
        ];
    }
}
