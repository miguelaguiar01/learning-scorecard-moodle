<?php

namespace local_learning_scorecard\models;

use local_learning_scorecard\constants\constants;

defined('MOODLE_INTERNAL') || die();

class xp_settings
{


    /**
     * Get Default XP settings for a course
     */
    public static function get_default_course_settings(): array
    {
        return constants::DEFAULT_XP_SETTINGS;
    }

    /**
     * Get XP settings for a course
     */
    public static function get_course_settings($courseId): array
    {
        global $DB;

        $settings = self::get_default_course_settings();

        // Get course-specific settings from database
        $records = $DB->get_records('config_plugins', [
            'plugin' => 'local_learning_scorecard_' . $courseId
        ]);

        foreach ($records as $record) {
            if (array_key_exists($record->name, $settings)) {
                $settings[$record->name] = is_numeric($record->value) ?
                    (str_contains($record->value, '.') ? (float)$record->value : (int)$record->value) :
                    $record->value;
            }
        }

        return $settings;
    }

    /**
     * Save XP settings for a course
     */
    public static function save_course_settings($courseId, $settings)
    {
        global $DB;

        $plugin_name = 'local_learning_scorecard_' . $courseId;

        foreach ($settings as $name => $value) {
            // Validate setting name
            if (!array_key_exists($name, self::get_default_course_settings())) {
                continue;
            }

            // Check if setting already exists
            $existing = $DB->get_record('config_plugins', [
                'plugin' => $plugin_name,
                'name' => $name
            ]);

            if ($existing) {
                // Update existing
                $existing->value = $value;
                $DB->update_record('config_plugins', $existing);
            } else {
                // Create new
                $record = new \stdClass();
                $record->plugin = $plugin_name;
                $record->name = $name;
                $record->value = $value;
                $DB->insert_record('config_plugins', $record);
            }
        }

        // Clear any relevant caches
        \cache::make('core', 'config')->purge();
    }

    /**
     * Reset course settings to defaults
     */
    public static function reset_course_settings($courseId)
    {
        global $DB;

        $plugin_name = 'local_learning_scorecard_' . $courseId;
        $DB->delete_records('config_plugins', ['plugin' => $plugin_name]);

        \cache::make('core', 'config')->purge();
    }

    /**
     * Get XP value for specific action in a course
     */
    public static function get_xp_value($courseId, $action)
    {
        $settings = self::get_course_settings($courseId);
        return isset($settings[$action]) ?? $settings[$action];
    }
}

