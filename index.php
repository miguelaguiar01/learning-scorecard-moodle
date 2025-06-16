<?php
require_once('../../config.php');
require_once('lib.php');

$courseid = required_param('id', PARAM_INT);
$tab = optional_param('tab', 'all', PARAM_ALPHA); // Default to 'all' tab
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

require_login($course);
$context = context_course::instance($courseid);

$PAGE->set_url('/local/learning_scorecard/index.php', array('id' => $courseid, 'tab' => $tab));
$PAGE->set_title(get_string('leaderboard', 'local_learning_scorecard'));
$PAGE->set_heading($course->fullname);
$PAGE->set_context($context);

// Add CSS for seamless integration
$PAGE->requires->css('/local/local_learning_scorecard/styles.css');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('leaderboard', 'local_learning_scorecard'));

// Define tabs
$tabs = array(
    'all' => get_string('leaderboard_all', 'local_learning_scorecard'),
    'quizzes' => get_string('leaderboard_quizzes', 'local_learning_scorecard'),
    'exercises' => get_string('leaderboard_exercises', 'local_learning_scorecard')
);

// Create tab navigation using Moodle's native tab system
$tabrows = array();
$tabrow = array();
foreach ($tabs as $tabkey => $tablabel) {
    $taburl = new moodle_url('/local/learning_scorecard/index.php', array('id' => $courseid, 'tab' => $tabkey));
    $tabrow[] = new tabobject($tabkey, $taburl, $tablabel);
}
$tabrows[] = $tabrow;

// Display tabs
print_tabs($tabrows, $tab);

// Get leaderboard data based on selected tab
switch ($tab) {
    case 'quizzes':
        $leaderboard = \local_learning_scorecard\leaderboard_manager::get_quiz_leaderboard($courseid);
        $columns = array(
            'rank' => get_string('rank', 'local_learning_scorecard'),
            'student' => get_string('student', 'local_learning_scorecard'),
            'quizzes_completed' => get_string('quizzes_completed', 'local_learning_scorecard'),
            'quiz_xp' => get_string('quiz_xp', 'local_learning_scorecard')
        );
        break;
    
    case 'exercises':
        $leaderboard = \local_learning_scorecard\leaderboard_manager::get_exercise_leaderboard($courseid);
        $columns = array(
            'rank' => get_string('rank', 'local_learning_scorecard'),
            'student' => get_string('student', 'local_learning_scorecard'),
            'exercises_completed' => get_string('exercises_completed', 'local_learning_scorecard'),
            'exercise_xp' => get_string('exercise_xp', 'local_learning_scorecard')
        );
        break;
    
    case 'all':
    default:
        $leaderboard = \local_learning_scorecard\leaderboard_manager::get_all_leaderboard($courseid);
        $columns = array(
            'rank' => get_string('rank', 'local_learning_scorecard'),
            'student' => get_string('student', 'local_learning_scorecard'),
            'total_xp' => get_string('total_xp', 'local_learning_scorecard'),
            'quiz_xp' => get_string('quiz_xp', 'local_learning_scorecard'),
            'exercise_xp' => get_string('exercise_xp', 'local_learning_scorecard'),
            'bonus_xp' => get_string('bonus_xp', 'local_learning_scorecard')
        );
        break;
}

// Display leaderboard table using Moodle's table class
$table = new html_table();
$table->attributes['class'] = 'generaltable';
$table->head = array_values($columns);

$rank = 1;
foreach ($leaderboard as $student) {
    $row = array();
    
    foreach ($columns as $key => $label) {
        switch ($key) {
            case 'rank':
                $row[] = $rank;
                break;
            case 'student':
                $row[] = $student['fullname'];
                break;
            default:
                $row[] = isset($student[$key]) ? $student[$key] : 0;
                break;
        }
    }
    
    $table->data[] = $row;
    $rank++;
}

echo html_writer::table($table);

// Add some statistics at the bottom
echo '<div class="leaderboard-stats mt-3">';
echo '<h4>' . get_string('statistics', 'local_learning_scorecard') . '</h4>';
echo '<p>' . get_string('total_students', 'local_learning_scorecard') . ': ' . count($leaderboard) . '</p>';

if (!empty($leaderboard)) {
    switch ($tab) {
        case 'quizzes':
            $total_xp = array_sum(array_column($leaderboard, 'quiz_xp'));
            echo '<p>' . get_string('total_quiz_xp', 'local_learning_scorecard') . ': ' . $total_xp . '</p>';
            break;
        case 'exercises':
            $total_xp = array_sum(array_column($leaderboard, 'exercise_xp'));
            echo '<p>' . get_string('total_exercise_xp', 'local_learning_scorecard') . ': ' . $total_xp . '</p>';
            break;
        case 'all':
        default:
            $total_xp = array_sum(array_column($leaderboard, 'total_xp'));
            echo '<p>' . get_string('total_all_xp', 'local_learning_scorecard') . ': ' . $total_xp . '</p>';
            break;
    }
}
echo '</div>';

echo $OUTPUT->footer();
?>