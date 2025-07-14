<?php
namespace local_learning_scorecard\models;

defined('MOODLE_INTERNAL') || die();

class xp_settings {
    
    // Default XP values
    const DEFAULT_SETTINGS = [
        'quiz_base_xp' => 100,
        'exercise_base_xp' => 50,
        'forum_post_xp' => 10,
        'grade_multiplier' => 2.0,
        'badge_bronze_xp' => 50,
        'badge_silver_xp' => 100,
        'badge_gold_xp' => 200,
        'badge_platinum_xp' => 500
    ];

     /**
     * Get Default XP settings for a course
     */
    public static function get_default_course_settings($courseid) {
        $settings = self::DEFAULT_SETTINGS;
        return $settings;
    }

    /**
     * Get XP settings for a course
     */
    public static function get_course_settings($courseid) {
        global $DB;
        
        $settings = self::DEFAULT_SETTINGS;
        
        // Get course-specific settings from database
        $records = $DB->get_records('config_plugins', [
            'plugin' => 'local_learning_scorecard_' . $courseid
        ]);
        
        foreach ($records as $record) {
            if (array_key_exists($record->name, $settings)) {
                $settings[$record->name] = is_numeric($record->value) ? 
                    (strpos($record->value, '.') !== false ? (float)$record->value : (int)$record->value) : 
                    $record->value;
            }
        }
        
        return $settings;
    }
    
    /**
     * Save XP settings for a course
     */
    public static function save_course_settings($courseid, $settings) {
        global $DB;
        
        $plugin_name = 'local_learning_scorecard_' . $courseid;
        
        foreach ($settings as $name => $value) {
            // Validate setting name
            if (!array_key_exists($name, self::DEFAULT_SETTINGS)) {
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
    public static function reset_course_settings($courseid) {
        global $DB;
        
        $plugin_name = 'local_learning_scorecard_' . $courseid;
        $DB->delete_records('config_plugins', ['plugin' => $plugin_name]);
        
        \cache::make('core', 'config')->purge();
    }
    
    /**
     * Get XP value for specific action in a course
     */
    public static function get_xp_value($courseid, $action) {
        $settings = self::get_course_settings($courseid);
        return isset($settings[$action]) ? $settings[$action] : 0;
    }
}
?>
