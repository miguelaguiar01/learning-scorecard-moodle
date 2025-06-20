<?php
namespace local_learning_scorecard;

defined('MOODLE_INTERNAL') || die();

class leaderboard_manager {

    /**
     * Get XP configuration for a course
     */
    private static function get_xp_config($courseid) {
        return \local_learning_scorecard\xp_settings_manager::get_course_settings($courseid);
    }
    
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
     * Get guild-focused leaderboard
     */
    public static function get_guild_leaderboard($courseid) {
        
        // Get all groups in this course
        $groups = groups_get_all_groups($courseid);
        
        if (empty($groups)) {
            return []; // No groups in this course
        }
        
        $guild_leaderboard = [];
        
        foreach ($groups as $group) {
            // Get all members of this group
            $members = groups_get_members($group->id);
            
            if (empty($members)) {
                continue; // Skip empty groups
            }
            
            $total_guild_xp = 0;
            $member_count = count($members);
            
            // Calculate total XP for all group members
            foreach ($members as $member) {
                $quiz_data = self::get_quiz_xp_data($member->id, $courseid);
                $exercise_data = self::get_exercise_xp_data($member->id, $courseid);
                $bonus_xp = self::get_bonus_xp($member->id, $courseid);
                
                $member_total_xp = $quiz_data['xp'] + $exercise_data['xp'] + $bonus_xp;
                $total_guild_xp += $member_total_xp;
            }
            
            $average_member_xp = $member_count > 0 ? round($total_guild_xp / $member_count) : 0;
            
            $guild_data = [
                'guild_id' => $group->id,
                'guild_name' => $group->name,
                'guild_members' => $member_count,
                'average_member_xp' => $average_member_xp,
                'total_guild_xp' => $total_guild_xp
            ];
            
            $guild_leaderboard[] = $guild_data;
        }
        
        // Sort by total guild XP (descending)
        usort($guild_leaderboard, function($a, $b) {
            return $b['total_guild_xp'] - $a['total_guild_xp'];
        });
        
        return $guild_leaderboard;
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
     * Get combined leaderboard (students ranked by sum of positions across all leaderboards)
     */
    public static function get_combined_leaderboard($courseid) {
        global $DB;
        
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
        $config = self::get_xp_config($courseid);
        $base_xp = $count * $config['quiz_base_xp'];
        $grade_bonus = $avg_grade * $config['grade_multiplier'];
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
        
        // Get assignments where grades are released (visible in gradebook)
        $assignment_sql = "SELECT 
            a.id as assignment_id,
            a.name,
            a.grade as max_grade,
            g.grade as user_grade,
            gi.grademax,
            gg.finalgrade,
            gg.hidden,
            gg.locked,
            gg.overridden
        FROM {assign} a
        JOIN {assign_submission} s ON s.assignment = a.id
        JOIN {assign_grades} g ON g.assignment = a.id AND g.userid = s.userid
        JOIN {grade_items} gi ON gi.iteminstance = a.id 
            AND gi.itemmodule = 'assign' 
            AND gi.courseid = a.course
            AND gi.itemtype = 'mod'
        JOIN {grade_grades} gg ON gg.itemid = gi.id AND gg.userid = s.userid
        WHERE 
            s.userid = ? 
            AND a.course = ? 
            AND s.status = 'submitted'
            AND s.latest = 1
            AND g.grade IS NOT NULL 
            AND g.grade >= 0
            AND gg.finalgrade IS NOT NULL
            AND gg.hidden = 0  -- Grade is not hidden from student
            AND (gg.locked > 0 OR gg.overridden > 0 OR gg.finalgrade IS NOT NULL)"; // Grade is finalized
        
        $assignments = $DB->get_records_sql($assignment_sql, [$userid, $courseid]);
        
        // Calculate assignment stats
        $assignment_count = count($assignments);
        $assignment_total_grade = 0;
        
        foreach ($assignments as $assignment) {
            // Use finalgrade from gradebook (most reliable)
            if ($assignment->grademax > 0) {
                $grade_percent = ($assignment->finalgrade / $assignment->grademax) * 100;
                $assignment_total_grade += $grade_percent;
            }
        }
        
        $assignment_avg_grade = $assignment_count > 0 ? $assignment_total_grade / $assignment_count : 0;
        
        // Get workshop submissions where grades are released
        $workshop_count = 0;
        $workshop_avg_grade = 0;
        
        if ($DB->get_manager()->table_exists('workshop_submissions')) {
            $workshop_sql = "SELECT 
                ws.id,
                w.name,
                w.grade as max_grade,
                ws.grade as user_grade,
                gi.grademax,
                gg.finalgrade,
                gg.hidden
            FROM {workshop_submissions} ws
            JOIN {workshop} w ON ws.workshopid = w.id
            JOIN {grade_items} gi ON gi.iteminstance = w.id 
                AND gi.itemmodule = 'workshop' 
                AND gi.courseid = w.course
                AND gi.itemtype = 'mod'
            JOIN {grade_grades} gg ON gg.itemid = gi.id AND gg.userid = ws.authorid
            WHERE 
                ws.authorid = ? 
                AND w.course = ?
                AND ws.grade IS NOT NULL
                AND ws.grade >= 0
                AND gg.finalgrade IS NOT NULL
                AND gg.hidden = 0  -- Grade is not hidden from student
                AND ws.published = 1"; // Workshop submission is published
            
            $workshops = $DB->get_records_sql($workshop_sql, [$userid, $courseid]);
            
            $workshop_count = count($workshops);
            $workshop_total_grade = 0;
            
            foreach ($workshops as $workshop) {
                if ($workshop->grademax > 0) {
                    $grade_percent = ($workshop->finalgrade / $workshop->grademax) * 100;
                    $workshop_total_grade += $grade_percent;
                }
            }
            
            $workshop_avg_grade = $workshop_count > 0 ? $workshop_total_grade / $workshop_count : 0;
        }
        
        // Calculate totals
        $total_count = $assignment_count + $workshop_count;
        
        if ($total_count == 0) {
            // No released grades yet
            return [
                'count' => 0,
                'xp' => 0,
                'pending' => self::get_pending_submissions_count($userid, $courseid)
            ];
        }
        
        // Calculate weighted average grade
        $total_avg_grade = (($assignment_avg_grade * $assignment_count) + 
                           ($workshop_avg_grade * $workshop_count)) / $total_count;
        
        // XP Calculation
        $config = self::get_xp_config($courseid);
        
        // Base XP for each graded exercise
        $base_xp = $total_count * $config['exercise_base_xp'];
        
        // Grade bonus based on performance
        $grade_bonus = ($total_avg_grade / 100) * $config['grade_multiplier'] * $total_count;
        
        $total_xp = $base_xp + $grade_bonus;
        
        // Debug logging
        if (debugging()) {
            mtrace("Exercise XP Calculation for user $userid in course $courseid:");
            mtrace("- Graded assignments: $assignment_count (avg: " . round($assignment_avg_grade, 2) . "%)");
            mtrace("- Graded workshops: $workshop_count (avg: " . round($workshop_avg_grade, 2) . "%)");
            mtrace("- Base XP: $base_xp, Grade bonus: " . round($grade_bonus, 2));
            mtrace("- Total XP: " . round($total_xp));
        }
        
        return [
            'count' => $total_count,
            'xp' => round($total_xp),
            'avg_grade' => round($total_avg_grade, 2),
            'details' => [
                'assignments' => $assignment_count,
                'workshops' => $workshop_count,
                'assignment_avg' => round($assignment_avg_grade, 2),
                'workshop_avg' => round($workshop_avg_grade, 2)
            ]
        ];
    }
    
    /**
     * Helper function to get count of pending (ungraded) submissions
     */
    private static function get_pending_submissions_count($userid, $courseid) {
        global $DB;
        
        // Count submitted but not yet graded assignments
        $pending_sql = "SELECT COUNT(DISTINCT a.id) as count
                       FROM {assign} a
                       JOIN {assign_submission} s ON s.assignment = a.id
                       LEFT JOIN {assign_grades} g ON g.assignment = a.id AND g.userid = s.userid
                       WHERE 
                           s.userid = ? 
                           AND a.course = ? 
                           AND s.status = 'submitted'
                           AND s.latest = 1
                           AND (g.grade IS NULL OR g.grade < 0)";
        
        $result = $DB->get_record_sql($pending_sql, [$userid, $courseid]);
        
        return $result ? $result->count : 0;
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
        
        $config = self::get_xp_config($courseid);
        $forum_xp = $forum_posts * $config['forum_post_xp'];
        
        // Space for additional bonus XP calculations:
        // - Course completion bonuses
        // - Perfect attendance bonuses
        // - Peer review bonuses
        // - Early submission bonuses
        // - Streak bonuses
        
        return $forum_xp;
    }
}
?>