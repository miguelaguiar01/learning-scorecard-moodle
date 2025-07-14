<?php
namespace local_learning_scorecard\views;

use html_table;
use html_writer;
use moodle_url;
use tabobject;

defined('MOODLE_INTERNAL') || die();

class leaderboard_renderer {
    const LEADERBOARD_TABS = [
        'all' => [
            'lang_key' => 'leaderboard_all',
            'columns' => [
                'rank' => 'rank',
                'student' => 'student',
                'total_xp' => 'total_xp',
                'quiz_xp' => 'quiz_xp',
                'exercise_xp' => 'exercise_xp',
                'bonus_xp' => 'bonus_xp'
            ]
        ],
        'quizzes' => [
            'lang_key' => 'leaderboard_quizzes',
            'columns' => [
                'rank' => 'rank',
                'student' => 'student',
                'quizzes_completed' => 'quizzes_completed',
                'quiz_xp' => 'quiz_xp'
            ]
        ],
        'exercises' => [
            'lang_key' => 'leaderboard_exercises',
            'columns' => [
                'rank' => 'rank',
                'student' => 'student',
                'exercises_completed' => 'exercises_completed',
                'exercise_xp' => 'exercise_xp'
            ]
        ],
        'guilds' => [
            'lang_key' => 'leaderboard_guilds',
            'columns' => [
                'rank' => 'rank',
                'guild_name' => 'guild_name',
                'guild_members' => 'member_count',
                'average_member_xp' => 'average_member_xp',
                'total_guild_xp' => 'total_guild_xp'
            ]
        ],
        'combined' => [
            'lang_key' => 'leaderboard_combined',
            'columns' => [
                'rank' => 'rank',
                'student' => 'student',
                'all_position' => 'all_position',
                'quiz_position' => 'quiz_position',
                'exercise_position' => 'exercise_position'
            ]
        ]
    ];
    
    public function render_leaderboard_page($courseid, $current_tab, $leaderboard_data) {
        $output = '';
        global $PAGE;
        $PAGE->requires->css('/local/learning_scorecard/styles/styles.css');
        $output .= $this->render_tabs($courseid, $current_tab);
        $output .= $this->render_leaderboard_table($current_tab, $leaderboard_data);
        $output .= $this->render_statistics($current_tab, $leaderboard_data);
        return $output;
    }
    
    private function render_tabs($courseid, $current_tab) {
        $tabs = [];
        foreach (self::LEADERBOARD_TABS as $tabkey => $config) {
            $taburl = new moodle_url('/local/learning_scorecard/index.php', [
                'id' => $courseid, 
                'tab' => $tabkey
            ]);
            $tabs[] = new tabobject($tabkey, $taburl, get_string($config['lang_key'], 'local_learning_scorecard'));
        }
        ob_start();
        print_tabs([$tabs], $current_tab);
        return ob_get_clean();
    }
    
    private function render_leaderboard_table($tab, $leaderboard_data) {
        if (empty($leaderboard_data)) {
            return html_writer::div(get_string('no_data', 'local_learning_scorecard'), 'alert alert-info');
        }
        $table = new html_table();
        $table->attributes['class'] = 'generaltable';
        $columns = $this->get_columns_for_tab($tab);
        $table->head = array_values($columns);
        $rank = 1;
        foreach ($leaderboard_data as $item) {
            $row = [];
            foreach ($columns as $key => $label) {
                switch ($key) {
                    case 'rank':
                        $row[] = $rank;
                        break;
                    case 'student':
                        $row[] = $item['fullname'];
                        break;
                    case 'guild_name':
                        $row[] = $item['guild_name'];
                        break;
                    default:
                        $row[] = isset($item[$key]) ? $item[$key] : 0;
                        break;
                }
            }
            $table->data[] = $row;
            $rank++;
        }
        return html_writer::table($table);
    }
    
    private function render_statistics($tab, $leaderboard_data) {
        if (empty($leaderboard_data)) {
            return '';
        }
        $output = html_writer::start_div('leaderboard-stats mt-3');
        $output .= html_writer::tag('h4', get_string('statistics', 'local_learning_scorecard'));
        $total_text = $this->get_total_text_for_tab($tab);
        $output .= html_writer::tag('p', $total_text . ': ' . count($leaderboard_data));
        $stats = $this->calculate_tab_statistics($tab, $leaderboard_data);
        if (!empty($stats)) {
            foreach ($stats as $stat) {
                $output .= html_writer::tag('p', $stat);
            }
        }
        $output .= html_writer::end_div();
        return $output;
    }
    
    private function get_columns_for_tab($tab) {
        if (!isset(self::LEADERBOARD_TABS[$tab])) {
            $tab = 'all';
        }
        $columns = [];
        foreach (self::LEADERBOARD_TABS[$tab]['columns'] as $key => $lang_key) {
            $columns[$key] = get_string($lang_key, 'local_learning_scorecard');
        }
        return $columns;
    }
    
    private function get_total_text_for_tab($tab) {
        switch ($tab) {
            case 'guilds':
                return get_string('total_guilds', 'local_learning_scorecard');
            case 'combined':
                return get_string('participating_students', 'local_learning_scorecard');
            default:
                return get_string('total_students', 'local_learning_scorecard');
        }
    }
    
    private function calculate_tab_statistics($tab, $leaderboard_data) {
        $stats = [];
        switch ($tab) {
            case 'quizzes':
                if (isset($leaderboard_data[0]['quiz_xp'])) {
                    $total_xp = array_sum(array_column($leaderboard_data, 'quiz_xp'));
                    $stats[] = get_string('total_quiz_xp', 'local_learning_scorecard') . ': ' . $total_xp;
                }
                break;
            case 'exercises':
                if (isset($leaderboard_data[0]['exercise_xp'])) {
                    $total_xp = array_sum(array_column($leaderboard_data, 'exercise_xp'));
                    $stats[] = get_string('total_exercise_xp', 'local_learning_scorecard') . ': ' . $total_xp;
                }
                break;
            case 'guilds':
                if (isset($leaderboard_data[0]['total_guild_xp'])) {
                    $total_xp = array_sum(array_column($leaderboard_data, 'total_guild_xp'));
                    $stats[] = get_string('total_guild_xp', 'local_learning_scorecard') . ': ' . $total_xp;
                }
                break;
            case 'combined':
                break; // Already show participating students count
            case 'all':
            default:
                if (isset($leaderboard_data[0]['total_xp'])) {
                    $total_xp = array_sum(array_column($leaderboard_data, 'total_xp'));
                    $stats[] = get_string('total_all_xp', 'local_learning_scorecard') . ': ' . $total_xp;
                }
                break;
        }
        return $stats;
    }
}

