<?php

namespace local_learning_scorecard\helpers;

defined('MOODLE_INTERNAL') || die();

class xp_calculator {

    /**
     * Calculate XP based on base value and grade performance
     */
    public static function calculate_base_with_grade_bonus($count, $base_xp, $avg_grade, $grade_multiplier): float|int {

        $base_total = $count * $base_xp;
        $grade_bonus = $avg_grade * $grade_multiplier;
        return $base_total + $grade_bonus;
    }

    /**
     * Calculate XP for exercises with weighted grade bonus
     */
    public static function calculate_exercise_xp($count, $base_xp, $avg_grade_percent, $grade_multiplier): float|int {

        $base_total = $count * $base_xp;
        $grade_bonus = ($avg_grade_percent / 100) * $grade_multiplier * $count;
        return $base_total + $grade_bonus;
    }

    /**
     * Calculate grade percentage from raw grade and maximum
     */
    public static function calculate_grade_percentage($grade, $max_grade): float|int {

        if ($max_grade <= 0) {
            return 0;
        }
        return ($grade / $max_grade) * 100;
    }

    /**
     * Calculate weighted average from multiple grade sources
     */
    public static function calculate_weighted_average($grades_and_counts): float|int {

        $total_weighted_sum = 0;
        $total_count = 0;

        foreach ($grades_and_counts as $data) {
            $total_weighted_sum += $data['avg_grade'] * $data['count'];
            $total_count += $data['count'];
        }

        return $total_count > 0 ? $total_weighted_sum / $total_count : 0;
    }

    /**
     * Round XP to nearest integer
     */
    public static function round_xp($xp): int {

        return round($xp);
    }
}