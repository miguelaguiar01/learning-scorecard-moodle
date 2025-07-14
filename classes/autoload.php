<?php
/**
 * Autoload file for local_learning_scorecard classes
 * 
 * This file ensures all classes are properly loaded and available
 * when using the new organized structure.
 */

defined('MOODLE_INTERNAL') || die();

// Load all model classes
require_once(__DIR__ . '/models/xp_settings.php');
require_once(__DIR__ . '/models/quiz_xp.php');
require_once(__DIR__ . '/models/exercise_xp.php');
require_once(__DIR__ . '/models/guild_xp.php');
require_once(__DIR__ . '/models/student_xp.php');

// Load all helper classes
require_once(__DIR__ . '/helpers/xp_calculator.php');
require_once(__DIR__ . '/helpers/grade_processor.php');

// Load all controller classes
require_once(__DIR__ . '/controllers/leaderboard_controller.php');

// Load all view classes
require_once(__DIR__ . '/views/leaderboard_renderer.php');
require_once(__DIR__ . '/views/settings_renderer.php');
?>
