<?php
require_once('../../config.php');
require_once('lib.php');

$courseid = required_param('id', PARAM_INT);
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

require_login($course);
$context = context_course::instance($courseid);
require_capability('moodle/course:manageactivities', $context); // Only teachers can access

$PAGE->set_url('/local/learning_scorecard/ls_settings.php', array('id' => $courseid));
$PAGE->set_title('Learning Scorecard Settings');
$PAGE->set_heading($course->fullname);
$PAGE->set_context($context);

echo $OUTPUT->header();

// Breadcrumb navigation
$leaderboard_url = new moodle_url('/local/learning_scorecard/index.php', array('id' => $courseid));
echo '<nav aria-label="breadcrumb">';
echo '<ol class="breadcrumb">';
echo '<li class="breadcrumb-item"><a href="' . $leaderboard_url . '">Leaderboard</a></li>';
echo '<li class="breadcrumb-item active" aria-current="page">Learning Scorecard Settings</li>';
echo '</ol>';
echo '</nav>';

echo $OUTPUT->heading('Learning Scorecard Settings');

// Handle form submission for any section
if ($_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()) {
    
    // Handle XP Settings
    if (isset($_POST['section']) && $_POST['section'] === 'ls_settings') {
        $settings = array(
            'quiz_base_xp' => required_param('quiz_base_xp', PARAM_INT),
            'exercise_base_xp' => required_param('exercise_base_xp', PARAM_INT),
            'forum_post_xp' => required_param('forum_post_xp', PARAM_INT),
            'grade_multiplier' => required_param('grade_multiplier', PARAM_FLOAT),
            'badge_bronze_xp' => required_param('badge_bronze_xp', PARAM_INT),
            'badge_silver_xp' => required_param('badge_silver_xp', PARAM_INT),
            'badge_gold_xp' => required_param('badge_gold_xp', PARAM_INT),
            'badge_platinum_xp' => required_param('badge_platinum_xp', PARAM_INT)
        );
        
        \local_learning_scorecard\xp_settings_manager::save_course_settings($courseid, $settings);
        echo $OUTPUT->notification('XP Settings saved successfully!', 'notifysuccess');
    }
    
    // Space for future settings sections:
    // - Badge Settings
    // - Leaderboard Display Settings  
    // - Notification Settings
    // - Competition Settings
}

// Get current settings
$current_xp_settings = \local_learning_scorecard\xp_settings_manager::get_course_settings($courseid);

?>

<div class="container-fluid">
    
    <!-- XP Settings Section -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h3 class="mb-0"><i class="fa fa-star"></i> Experience Points (XP) Settings</h3>
            <small>Configure how students earn experience points in your course</small>
        </div>
        <div class="card-body">
            
            <form method="post" id="xp-settings-form">
                <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">
                <input type="hidden" name="section" value="xp_settings">
                
                <div class="row">
                    <!-- Base XP Values -->
                    <div class="col-md-6">
                        <h5 class="text-primary mb-3">📚 Activity Base XP</h5>
                        
                        <div class="form-group mb-3">
                            <label for="quiz_base_xp" class="form-label fw-bold">Quiz Base XP</label>
                            <input type="number" class="form-control" name="quiz_base_xp" id="quiz_base_xp" 
                                   value="<?php echo $current_xp_settings['quiz_base_xp']; ?>" min="1" max="1000" required>
                            <small class="form-text text-muted">XP awarded for completing each quiz</small>
                        </div>

                        <div class="form-group mb-3">
                            <label for="exercise_base_xp" class="form-label fw-bold">Exercise Base XP</label>
                            <input type="number" class="form-control" name="exercise_base_xp" id="exercise_base_xp" 
                                   value="<?php echo $current_xp_settings['exercise_base_xp']; ?>" min="1" max="1000" required>
                            <small class="form-text text-muted">XP awarded for completing assignments and exercises</small>
                        </div>

                        <div class="form-group mb-3">
                            <label for="forum_post_xp" class="form-label fw-bold">Forum Post XP</label>
                            <input type="number" class="form-control" name="forum_post_xp" id="forum_post_xp" 
                                   value="<?php echo $current_xp_settings['forum_post_xp']; ?>" min="1" max="100" required>
                            <small class="form-text text-muted">XP awarded for each forum post or reply</small>
                        </div>

                        <div class="form-group mb-3">
                            <label for="grade_multiplier" class="form-label fw-bold">Grade Multiplier</label>
                            <input type="number" class="form-control" name="grade_multiplier" id="grade_multiplier" 
                                   value="<?php echo $current_xp_settings['grade_multiplier']; ?>" min="0.1" max="10" step="0.1" required>
                            <small class="form-text text-muted">Multiplier applied to grades for bonus XP (higher grades = more XP)</small>
                        </div>
                    </div>

                    <!-- Badge XP Values -->
                    <div class="col-md-6">
                        <h5 class="text-warning mb-3">🏆 Badge XP Rewards</h5>
                        
                        <div class="form-group mb-3">
                            <label for="badge_bronze_xp" class="form-label fw-bold">🥉 Bronze Badge XP</label>
                            <input type="number" class="form-control" name="badge_bronze_xp" id="badge_bronze_xp" 
                                   value="<?php echo $current_xp_settings['badge_bronze_xp']; ?>" min="1" max="1000" required>
                        </div>

                        <div class="form-group mb-3">
                            <label for="badge_silver_xp" class="form-label fw-bold">🥈 Silver Badge XP</label>
                            <input type="number" class="form-control" name="badge_silver_xp" id="badge_silver_xp" 
                                   value="<?php echo $current_xp_settings['badge_silver_xp']; ?>" min="1" max="1000" required>
                        </div>

                        <div class="form-group mb-3">
                            <label for="badge_gold_xp" class="form-label fw-bold">🥇 Gold Badge XP</label>
                            <input type="number" class="form-control" name="badge_gold_xp" id="badge_gold_xp" 
                                   value="<?php echo $current_xp_settings['badge_gold_xp']; ?>" min="1" max="1000" required>
                        </div>

                        <div class="form-group mb-3">
                            <label for="badge_platinum_xp" class="form-label fw-bold">🏆 Platinum Badge XP</label>
                            <input type="number" class="form-control" name="badge_platinum_xp" id="badge_platinum_xp" 
                                   value="<?php echo $current_xp_settings['badge_platinum_xp']; ?>" min="1" max="2000" required>
                        </div>
                    </div>
                </div>

                <!-- Action buttons for XP Settings -->
                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-primary btn-lg me-3">
                        <i class="fa fa-save"></i> Save XP Settings
                    </button>
                    <button type="button" class="btn btn-warning btn-lg" onclick="resetXPDefaults()">
                        <i class="fa fa-refresh"></i> Reset to Defaults
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Space for Future Settings Sections -->
    
    <!-- Badge Management Section (Future) -->
    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <h3 class="mb-0"><i class="fa fa-trophy"></i> Badge Management <span class="badge bg-warning text-dark">Coming Soon</span></h3>
            <small>Configure badge requirements and unlock conditions</small>
        </div>
        <div class="card-body">
            <p class="text-muted">This section will allow you to:</p>
            <ul class="text-muted">
                <li>Enable/disable specific badges</li>
                <li>Customize badge requirements</li>
                <li>Set badge unlock conditions</li>
                <li>Manage badge icons and descriptions</li>
            </ul>
        </div>
    </div>

    <!-- Leaderboard Display Section (Future) -->
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h3 class="mb-0"><i class="fa fa-chart-line"></i> Leaderboard Display <span class="badge bg-warning text-dark">Coming Soon</span></h3>
            <small>Customize how leaderboards are displayed to students</small>
        </div>
        <div class="card-body">
            <p class="text-muted">This section will allow you to:</p>
            <ul class="text-muted">
                <li>Show/hide specific leaderboard tabs</li>
                <li>Customize leaderboard refresh frequency</li>
                <li>Set privacy options (anonymous mode)</li>
                <li>Configure point display format</li>
            </ul>
        </div>
    </div>

    <!-- Navigation -->
    <div class="text-center mt-4">
        <a href="<?php echo $leaderboard_url; ?>" class="btn btn-secondary btn-lg">
            <i class="fa fa-arrow-left"></i> Back to Leaderboard
        </a>
    </div>
</div>

<script>
function resetXPDefaults() {
    if (confirm('Are you sure you want to reset all XP settings to default values?')) {
        document.getElementById('quiz_base_xp').value = 100;
        document.getElementById('exercise_base_xp').value = 50;
        document.getElementById('forum_post_xp').value = 10;
        document.getElementById('grade_multiplier').value = 2.0;
        document.getElementById('badge_bronze_xp').value = 50;
        document.getElementById('badge_silver_xp').value = 100;
        document.getElementById('badge_gold_xp').value = 200;
        document.getElementById('badge_platinum_xp').value = 500;
    }
}
</script>

<?php
echo $OUTPUT->footer();
?>