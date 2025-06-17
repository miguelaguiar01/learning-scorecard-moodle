<?php
defined('MOODLE_INTERNAL') || die();

function local_learning_scorecard_extend_navigation_course($navigation, $course, $context) {
    // Add leaderboard for all enrolled users
    if (is_enrolled($context) || has_capability('moodle/course:manageactivities', $context)) {
        $url = new moodle_url('/local/learning_scorecard/index.php', array('id' => $course->id));
        $navigation->add('Leaderboard', $url, navigation_node::TYPE_CUSTOM, null, 'leaderboard');
    }
    
    // Add settings for teachers only
    if (has_capability('moodle/course:manageactivities', $context)) {
        $settings_url = new moodle_url('/local/learning_scorecard/ls_settings.php', array('id' => $course->id));
        $navigation->add('Learning Scorecard Settings', $settings_url, navigation_node::TYPE_CUSTOM, null, 'ls_settings');
    }
}
?>