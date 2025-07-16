<?php

namespace local_learning_scorecard\models;

defined('MOODLE_INTERNAL') || die();

class guild_xp
{

    /**
     * Get guild leaderboard for a course
     */
    public static function get_guild_leaderboard($courseId): array
    {

        // Get all groups in this course
        $groups = groups_get_all_groups($courseId);

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

            $guild_data = self::calculate_guild_xp($group, $members, $courseId);
            $guild_leaderboard[] = $guild_data;
        }

        // Sort by total guild XP (descending)
        usort($guild_leaderboard, function ($a, $b) {
            return $b['total_guild_xp'] - $a['total_guild_xp'];
        });

        return $guild_leaderboard;
    }

    /**
     * Calculate XP for a single guild
     */
    private static function calculate_guild_xp($group, $members, $courseId): array
    {
        $total_guild_xp = 0;
        $member_count = count($members);
        $member_details = [];

        // Calculate total XP for all group members
        foreach ($members as $member) {
            $member_xp = student_xp::get_student_total_xp($member->id, $courseId);
            $total_guild_xp += $member_xp['total_xp'];

            $member_details[] = [
                'userid' => $member->id,
                'fullname' => fullname($member),
                'total_xp' => $member_xp['total_xp']
            ];
        }

        $average_member_xp = $member_count > 0 ? round($total_guild_xp / $member_count) : 0;

        return [
            'guild_id' => $group->id,
            'guild_name' => $group->name,
            'guild_members' => $member_count,
            'average_member_xp' => $average_member_xp,
            'total_guild_xp' => $total_guild_xp,
            'members' => $member_details
        ];
    }

    /**
     * Get guild information for a specific group
     */
    public static function get_guild_info($groupid, $courseId): ?array
    {
        $group = groups_get_group($groupid);

        if (!$group) {
            return null;
        }

        $members = groups_get_members($groupid);

        if (empty($members)) {
            return [
                'guild_id' => $group->id,
                'guild_name' => $group->name,
                'guild_members' => 0,
                'average_member_xp' => 0,
                'total_guild_xp' => 0,
                'members' => []
            ];
        }

        return self::calculate_guild_xp($group, $members, $courseId);
    }

    /**
     * Get user's guild information
     */
    public static function get_user_guild_info($userId, $courseId)
    {
        $groups = groups_get_user_groups($courseId, $userId);

        if (empty($groups) || empty($groups[0])) {
            return null; // User not in any group
        }

        // Get the first group (assuming user is in one group)
        $groupid = reset($groups[0]);

        return self::get_guild_info($groupid, $courseId);
    }

    /**
     * Get guild rankings
     */
    public static function get_guild_rankings($courseId)
    {
        $leaderboard = self::get_guild_leaderboard($courseId);

        $rankings = [];
        foreach ($leaderboard as $position => $guild) {
            $rankings[] = [
                'position' => $position + 1,
                'guild_id' => $guild['guild_id'],
                'guild_name' => $guild['guild_name'],
                'total_guild_xp' => $guild['total_guild_xp'],
                'average_member_xp' => $guild['average_member_xp'],
                'member_count' => $guild['guild_members']
            ];
        }

        return $rankings;
    }

    /**
     * Get guild statistics
     */
    public static function get_guild_stats($courseId)
    {
        $groups = groups_get_all_groups($courseId);

        if (empty($groups)) {
            return [
                'total_guilds' => 0,
                'total_members' => 0,
                'avg_members_per_guild' => 0,
                'guilds_with_members' => 0
            ];
        }

        $total_guilds = count($groups);
        $total_members = 0;
        $guilds_with_members = 0;

        foreach ($groups as $group) {
            $members = groups_get_members($group->id);
            $member_count = count($members);

            if ($member_count > 0) {
                $guilds_with_members++;
                $total_members += $member_count;
            }
        }

        $avg_members_per_guild = $guilds_with_members > 0 ? round($total_members / $guilds_with_members, 1) : 0;

        return [
            'total_guilds' => $total_guilds,
            'total_members' => $total_members,
            'avg_members_per_guild' => $avg_members_per_guild,
            'guilds_with_members' => $guilds_with_members
        ];
    }
}
