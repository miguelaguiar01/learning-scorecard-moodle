<?php

namespace local_learning_scorecard\models;

defined('MOODLE_INTERNAL') || die();

class student_xp
{

    /**
     * Get total XP for a student
     */
    public static function get_student_total_xp($userid, $courseid): array
    {
        $quiz_data = quiz_xp::get_quiz_xp_data($userid, $courseid);
        $exercise_data = exercise_xp::get_exercise_xp_data($userid, $courseid);
        $bonus_xp = self::get_bonus_xp($userid, $courseid);

        return [
            'userid' => $userid,
            'quiz_xp' => $quiz_data['xp'],
            'exercise_xp' => $exercise_data['xp'],
            'bonus_xp' => $bonus_xp,
            'total_xp' => $quiz_data['xp'] + $exercise_data['xp'] + $bonus_xp
        ];
    }

    /**
     * Get detailed XP breakdown for a student
     */
    public static function get_student_xp_breakdown($userid, $courseid): ?array
    {
        global $DB;

        $user = $DB->get_record('user', ['id' => $userid]);
        if (!$user) {
            return null;
        }

        $quiz_data = quiz_xp::get_quiz_xp_data($userid, $courseid);
        $exercise_data = exercise_xp::get_exercise_xp_data($userid, $courseid);
        $bonus_xp = self::get_bonus_xp($userid, $courseid);

        return [
            'userid' => $userid,
            'fullname' => fullname($user),
            'quiz_data' => $quiz_data,
            'exercise_data' => $exercise_data,
            'bonus_xp' => $bonus_xp,
            'total_xp' => $quiz_data['xp'] + $exercise_data['xp'] + $bonus_xp
        ];
    }

    /**
     * Get all students in a course with their XP data
     */
    public static function get_all_students_xp($courseid): array
    {
        $context = \context_course::instance($courseid);
        $students = get_enrolled_users($context, 'mod/quiz:attempt');

        $students_xp = [];

        foreach ($students as $student) {
            $xp_data = self::get_student_total_xp($student->id, $courseid);
            $xp_data['fullname'] = fullname($student);
            $students_xp[] = $xp_data;
        }

        return $students_xp;
    }

    /**
     * Calculate bonus XP (forum participation, etc.)
     */
    private static function get_bonus_xp($userid, $courseid): float
    {
        global $DB;

        // Forum posts XP
        $forum_sql = "SELECT COUNT(*) as count
                      FROM {forum_posts} fp
                      JOIN {forum_discussions} fd ON fp.discussion = fd.id
                      JOIN {forum} f ON fd.forum = f.id
                      WHERE fp.userid = ? AND f.course = ?";

        $forum_result = $DB->get_record_sql($forum_sql, [$userid, $courseid]);
        $forum_posts = $forum_result ? $forum_result->count : 0;

        $config = xp_settings::get_course_settings($courseid);

        // Space for additional bonus XP calculations:
        // - Course completion bonuses
        // - Perfect attendance bonuses
        // - Peer review bonuses
        // - Early submission bonuses
        // - Streak bonuses

        return $forum_posts * $config['forum_post_xp'];
    }

    /**
     * Get student's activity summary
     */
    public static function get_student_activity_summary($userid, $courseid): array
    {
        $quiz_stats = quiz_xp::get_quiz_stats($userid, $courseid);
        $exercise_stats = exercise_xp::get_exercise_stats($userid, $courseid);
        $bonus_xp = self::get_bonus_xp($userid, $courseid);

        return [
            'quiz_stats' => $quiz_stats,
            'exercise_stats' => $exercise_stats,
            'bonus_xp' => $bonus_xp,
            'last_activity' => self::get_last_activity($userid, $courseid)
        ];
    }

    /**
     * Get student's last activity timestamp
     */
    private static function get_last_activity($userid, $courseid)
    {
        global $DB;

        $sql = "SELECT MAX(timeaccess) as last_access
                FROM {user_lastaccess}
                WHERE userid = ? AND courseid = ?";

        $result = $DB->get_record_sql($sql, [$userid, $courseid]);

        return $result && $result->last_access ? $result->last_access : null;
    }
}