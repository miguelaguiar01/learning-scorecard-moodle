<?php
/**
 * Event observers for Learning Scorecard plugin
 *
 * @package    local_learning_scorecard
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = array(
    array(
        'eventname' => '\mod_quiz\event\attempt_submitted',
        'callback' => '\local_learning_scorecard\observer\quiz_observer::attempt_submitted',
        'includefile' => null,
        'internal' => true
    ),
    array(
        'eventname' => '\mod_assign\event\assessable_submitted',
        'callback' => '\local_learning_scorecard\observer\assignment_observer::assignment_submitted',
        'includefile' => null,
        'internal' => true
    ),
    array(
        'eventname' => '\mod_forum\event\post_created',
        'callback' => '\local_learning_scorecard\observer\forum_observer::post_created',
        'includefile' => null,
        'internal' => true
    )
);