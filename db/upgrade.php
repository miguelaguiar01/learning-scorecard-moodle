<?php
/**
 * Database upgrade script for Learning Scorecard plugin
 *
 * This file is automatically executed when the plugin version changes
 *
 * @package    local_learning_scorecard
 * @copyright  2024 Miguel d'Aguiar
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute learning scorecard upgrade from the given old version
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_learning_scorecard_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager(); // Loads the database manager

    // Create the main tables for the learning scorecard system
    if ($oldversion < 2024122504) {

        // Define table local_ls_student_xp
        $table = new xmldb_table('local_ls_student_xp');

        // Adding fields to table local_ls_student_xp
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('quiz_xp', XMLDB_TYPE_NUMBER, '10,2', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('exercise_xp', XMLDB_TYPE_NUMBER, '10,2', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('bonus_xp', XMLDB_TYPE_NUMBER, '10,2', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('total_xp', XMLDB_TYPE_NUMBER, '10,2', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('rank', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'Newbie');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table local_ls_student_xp
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('userid_fk', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
        $table->add_key('courseid_fk', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);

        // Adding indexes to table local_ls_student_xp
        $table->add_index('userid_courseid', XMLDB_INDEX_UNIQUE, ['userid', 'courseid']);
        $table->add_index('courseid_totalxp', XMLDB_INDEX_NOTUNIQUE, ['courseid', 'total_xp']);
        $table->add_index('timemodified', XMLDB_INDEX_NOTUNIQUE, ['timemodified']);

        // Conditionally launch create table for local_ls_student_xp
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table local_ls_xp_settings
        $table = new xmldb_table('local_ls_xp_settings');

        // Adding fields to table local_ls_xp_settings
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('quiz_base_xp', XMLDB_TYPE_NUMBER, '8,2', null, XMLDB_NOTNULL, null, '100');
        $table->add_field('exercise_base_xp', XMLDB_TYPE_NUMBER, '8,2', null, XMLDB_NOTNULL, null, '50');
        $table->add_field('forum_post_xp', XMLDB_TYPE_NUMBER, '8,2', null, XMLDB_NOTNULL, null, '10');
        $table->add_field('perfect_score_multiplier', XMLDB_TYPE_NUMBER, '8,2', null, XMLDB_NOTNULL, null, '1.5');
        $table->add_field('enabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table local_ls_xp_settings
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('courseid_fk', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);

        // Note: No additional indexes needed - foreign key will create index automatically

        // Conditionally launch create table for local_ls_xp_settings
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table local_ls_xp_history
        $table = new xmldb_table('local_ls_xp_history');

        // Adding fields to table local_ls_xp_history
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('activity_type', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('activity_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('xp_earned', XMLDB_TYPE_NUMBER, '8,2', null, XMLDB_NOTNULL, null, null);
        $table->add_field('reason', XMLDB_TYPE_TEXT, 'small', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table local_ls_xp_history
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('userid_fk', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
        $table->add_key('courseid_fk', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);

        // Adding indexes to table local_ls_xp_history
        $table->add_index('userid_courseid', XMLDB_INDEX_NOTUNIQUE, ['userid', 'courseid']);
        $table->add_index('activity_type', XMLDB_INDEX_NOTUNIQUE, ['activity_type']);
        $table->add_index('timecreated', XMLDB_INDEX_NOTUNIQUE, ['timecreated']);

        // Conditionally launch create table for local_ls_xp_history
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Learning scorecard savepoint reached
        upgrade_plugin_savepoint(true, 2024122504, 'local', 'learning_scorecard');
    }

    // Example upgrade step: Add a new table
    if ($oldversion < 2024061256) {

        // Define table local_ls_achievements to be created
        $table = new xmldb_table('local_ls_achievements');

        // Adding fields to table local_ls_achievements
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('achievement_type', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
        $table->add_field('achievement_data', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table local_ls_achievements
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
        $table->add_key('courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);

        // Adding indexes to table local_ls_achievements
        $table->add_index('userid_courseid', XMLDB_INDEX_NOTUNIQUE, ['userid', 'courseid']);
        $table->add_index('achievement_type', XMLDB_INDEX_NOTUNIQUE, ['achievement_type']);

        // Conditionally launch create table for local_ls_achievements
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Learning scorecard savepoint reached
        upgrade_plugin_savepoint(true, 2024061256, 'local', 'learning_scorecard');
    }

    // Example upgrade step: Modify an existing field
    if ($oldversion < 2024061257) {

        // Define field reason to be modified
        $table = new xmldb_table('local_ls_xp_history');
        $field = new xmldb_field('reason', XMLDB_TYPE_TEXT, 'medium', null, null, null, null, 'xp_earned');

        // Launch change of type for field reason
        $dbman->change_field_type($table, $field);

        // Learning scorecard savepoint reached
        upgrade_plugin_savepoint(true, 2024061257, 'local', 'learning_scorecard');
    }

    // Example upgrade step: Rename a field
    if ($oldversion < 2024061258) {

        // Define field old_name to be renamed to new_name
        $table = new xmldb_table('local_ls_student_xp');
        $field = new xmldb_field('level', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, '1', 'total_xp');

        // Launch rename field old_name -> new_name
        // $dbman->rename_field($table, $field, 'student_level');

        // Learning scorecard savepoint reached
        // upgrade_plugin_savepoint(true, 2024061258, 'local', 'learning_scorecard');
    }

    // Convert level field to rank field with string values
    if ($oldversion < 2024122510) {

        // Define field rank to be added to local_ls_student_xp
        $table = new xmldb_table('local_ls_student_xp');
        $field = new xmldb_field('rank', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'Newbie', 'total_xp');

        // Conditionally launch add field rank
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Convert existing level values to rank names
        $DB->execute("
            UPDATE {local_ls_student_xp} 
            SET rank = CASE 
                WHEN level = 1 OR total_xp < 100 THEN 'Newbie'
                WHEN level = 2 OR (total_xp >= 100 AND total_xp < 200) THEN 'Rookie'
                WHEN level = 3 OR (total_xp >= 200 AND total_xp < 300) THEN 'Skilled'
                WHEN level = 4 OR (total_xp >= 300 AND total_xp < 400) THEN 'Expert'
                WHEN level = 5 OR (total_xp >= 400 AND total_xp < 500) THEN 'Master'
                ELSE 'Legendary'
            END
        ");

        // Drop the old level field
        $field = new xmldb_field('level');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Learning scorecard savepoint reached
        upgrade_plugin_savepoint(true, 2024122510, 'local', 'learning_scorecard');
    }

    // Add more upgrade steps as needed...
    // Each step should:
    // 1. Check version
    // 2. Define database changes
    // 3. Execute changes conditionally
    // 4. Call upgrade_plugin_savepoint()

    return true;
}
