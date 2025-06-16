<?php
function local_learning_scorecard_extend_navigation_course($navigation, $course, $context) {
    if (has_capability('moodle/course:view', $context)) {
        $url = new moodle_url('/local/learning_scorecard/index.php', array('id' => $course->id));
        $navigation->add(get_string('leaderboard', 'local_learning_scorecard'), $url, 
                        navigation_node::TYPE_CUSTOM, null, 'local_learning_scorecard');
    }
}