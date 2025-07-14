<?php
namespace local_learning_scorecard\views;

use html_writer;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

class settings_renderer {
    
    const XP_SETTINGS_FIELDS = [
        'base_xp' => [
            'title' => '📚 Activity Base XP',
            'fields' => [
                'quiz_base_xp' => [
                    'type' => 'number',
                    'label' => 'Quiz Base XP',
                    'help' => 'XP awarded for completing each quiz',
                    'min' => 1,
                    'max' => 1000
                ],
                'exercise_base_xp' => [
                    'type' => 'number',
                    'label' => 'Exercise Base XP',
                    'help' => 'XP awarded for completing assignments and exercises',
                    'min' => 1,
                    'max' => 1000
                ],
                'forum_post_xp' => [
                    'type' => 'number',
                    'label' => 'Forum Post XP',
                    'help' => 'XP awarded for each forum post or reply',
                    'min' => 1,
                    'max' => 1000
                ],
                'grade_multiplier' => [
                    'type' => 'number',
                    'label' => 'Grade Multiplier',
                    'help' => 'Multiplier applied to grades for bonus XP (higher grades = more XP)',
                    'min' => 0.1,
                    'max' => 10,
                    'step' => 0.1
                ]
            ]
        ],
        'badge_xp' => [
            'title' => '🏆 Badge XP Rewards',
            'fields' => [
                'badge_bronze_xp' => [
                    'type' => 'number',
                    'label' => '🥉 Bronze Badge XP',
                    'min' => 1,
                    'max' => 1000
                ],
                'badge_silver_xp' => [
                    'type' => 'number',
                    'label' => '🥈 Silver Badge XP',
                    'min' => 1,
                    'max' => 1000
                ],
                'badge_gold_xp' => [
                    'type' => 'number',
                    'label' => '🥇 Gold Badge XP',
                    'min' => 1,
                    'max' => 1000
                ],
                'badge_platinum_xp' => [
                    'type' => 'number',
                    'label' => '🏆 Platinum Badge XP',
                    'min' => 1,
                    'max' => 2000
                ]
            ]
        ]
    ];
    
    public function render_settings_page($courseid, $current_settings) {
        $output = '';
        
        $output .= $this->render_breadcrumb($courseid);
        $output .= $this->render_main_container($courseid, $current_settings);
        
        return $output;
    }
    
    private function render_breadcrumb($courseid) {
        $leaderboard_url = new moodle_url('/local/learning_scorecard/index.php', ['id' => $courseid]);
        
        $output = html_writer::start_tag('nav', ['aria-label' => 'breadcrumb']);
        $output .= html_writer::start_tag('ol', ['class' => 'breadcrumb']);
        $output .= html_writer::tag('li', 
            html_writer::link($leaderboard_url, 'Leaderboard'),
            ['class' => 'breadcrumb-item']
        );
        $output .= html_writer::tag('li', 
            'Learning Scorecard Settings',
            ['class' => 'breadcrumb-item active', 'aria-current' => 'page']
        );
        $output .= html_writer::end_tag('ol');
        $output .= html_writer::end_tag('nav');
        
        return $output;
    }
    
    private function render_main_container($courseid, $current_settings) {
        $output = html_writer::start_div('container-fluid');
        
        // XP Settings Section
        $output .= $this->render_xp_settings_card($courseid, $current_settings);
        
        // Future settings sections
        $output .= $this->render_future_sections();
        
        // Navigation
        $output .= $this->render_navigation($courseid);
        
        $output .= html_writer::end_div();
        
        return $output;
    }
    
    private function render_xp_settings_card($courseid, $current_settings) {
        $output = html_writer::start_div('card mb-4');
        
        // Card header
        $output .= html_writer::start_div('card-header bg-primary text-white');
        $output .= html_writer::tag('h3', 
            '<i class="fa fa-star"></i> Experience Points (XP) Settings',
            ['class' => 'mb-0']
        );
        $output .= html_writer::tag('small', 'Configure how students earn experience points in your course');
        $output .= html_writer::end_div();
        
        // Card body
        $output .= html_writer::start_div('card-body');
        $output .= $this->render_xp_settings_form($courseid, $current_settings);
        $output .= html_writer::end_div();
        
        $output .= html_writer::end_div();
        
        return $output;
    }
    
    private function render_xp_settings_form($courseid, $current_settings) {
        $output = html_writer::start_tag('form', [
            'method' => 'post',
            'id' => 'xp-settings-form'
        ]);
        
        // Hidden fields
        $output .= html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'sesskey',
            'value' => sesskey()
        ]);
        $output .= html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'section',
            'value' => 'xp_settings'
        ]);
        
        // Form fields
        $output .= html_writer::start_div('row');
        
        foreach (self::XP_SETTINGS_FIELDS as $section_key => $section) {
            $output .= html_writer::start_div('col-md-6');
            $output .= html_writer::tag('h5', $section['title'], ['class' => 'text-primary mb-3']);
            
            foreach ($section['fields'] as $field_key => $field) {
                $output .= $this->render_form_field($field_key, $field, $current_settings);
            }
            
            $output .= html_writer::end_div();
        }
        
        $output .= html_writer::end_div();
        
        // Action buttons
        $output .= $this->render_action_buttons();
        
        $output .= html_writer::end_tag('form');
        
        return $output;
    }
    
    private function render_form_field($field_key, $field_config, $current_settings) {
        $output = html_writer::start_div('form-group mb-3');
        
        // Label
        $output .= html_writer::tag('label', $field_config['label'], [
            'for' => $field_key,
            'class' => 'form-label fw-bold'
        ]);
        
        // Input field
        $input_attributes = [
            'type' => $field_config['type'],
            'class' => 'form-control',
            'name' => $field_key,
            'id' => $field_key,
            'value' => $current_settings[$field_key],
            'required' => true
        ];
        
        // Add field-specific attributes
        if (isset($field_config['min'])) {
            $input_attributes['min'] = $field_config['min'];
        }
        if (isset($field_config['max'])) {
            $input_attributes['max'] = $field_config['max'];
        }
        if (isset($field_config['step'])) {
            $input_attributes['step'] = $field_config['step'];
        }
        
        $output .= html_writer::empty_tag('input', $input_attributes);
        
        // Help text
        if (isset($field_config['help'])) {
            $output .= html_writer::tag('small', $field_config['help'], [
                'class' => 'form-text text-muted'
            ]);
        }
        
        $output .= html_writer::end_div();
        
        return $output;
    }
    
    private function render_action_buttons() {
        $output = html_writer::start_div('text-center mt-4');
        
        // Save button
        $output .= html_writer::tag('button', 
            '<i class="fa fa-save"></i> Save XP Settings',
            [
                'type' => 'submit',
                'class' => 'btn btn-primary btn-lg me-3'
            ]
        );
        
        // Reset button
        $output .= html_writer::tag('button', 
            '<i class="fa fa-refresh"></i> Reset to Defaults',
            [
                'type' => 'submit',
                'name' => 'action',
                'value' => 'reset',
                'class' => 'btn btn-warning btn-lg',
                'onclick' => "return confirm('Are you sure you want to reset all settings to default values?')"
            ]
        );
        
        $output .= html_writer::end_div();
        
        return $output;
    }
    
    private function render_future_sections() {
        $output = '';
        
        // Badge Management Section
        $output .= $this->render_future_section(
            'Badge Management',
            'fa fa-trophy',
            'bg-success',
            'Configure badge requirements and unlock conditions',
            [
                'Enable/disable specific badges',
                'Customize badge requirements',
                'Set badge unlock conditions',
                'Manage badge icons and descriptions'
            ]
        );
        
        // Leaderboard Display Section
        $output .= $this->render_future_section(
            'Leaderboard Display',
            'fa fa-chart-line',
            'bg-info',
            'Customize how leaderboards are displayed to students',
            [
                'Show/hide specific leaderboard tabs',
                'Customize leaderboard refresh frequency',
                'Set privacy options (anonymous mode)',
                'Configure point display format'
            ]
        );
        
        return $output;
    }
    
    private function render_future_section($title, $icon, $header_class, $description, $features) {
        $output = html_writer::start_div('card mb-4');
        
        // Header
        $output .= html_writer::start_div("card-header $header_class text-white");
        $output .= html_writer::tag('h3', 
            "<i class=\"$icon\"></i> $title " . 
            '<span class="badge bg-warning text-dark">Coming Soon</span>',
            ['class' => 'mb-0']
        );
        $output .= html_writer::tag('small', $description);
        $output .= html_writer::end_div();
        
        // Body
        $output .= html_writer::start_div('card-body');
        $output .= html_writer::tag('p', 'This section will allow you to:', ['class' => 'text-muted']);
        
        $output .= html_writer::start_tag('ul', ['class' => 'text-muted']);
        foreach ($features as $feature) {
            $output .= html_writer::tag('li', $feature);
        }
        $output .= html_writer::end_tag('ul');
        
        $output .= html_writer::end_div();
        
        $output .= html_writer::end_div();
        
        return $output;
    }
    
    private function render_navigation($courseid) {
        $leaderboard_url = new moodle_url('/local/learning_scorecard/index.php', ['id' => $courseid]);
        
        $output = html_writer::start_div('text-center mt-4');
        $output .= html_writer::link(
            $leaderboard_url,
            '<i class="fa fa-arrow-left"></i> Back to Leaderboard',
            ['class' => 'btn btn-secondary btn-lg']
        );
        $output .= html_writer::end_div();
        
        return $output;
    }
    
    public function render_reset_defaults_script() {
        return '
        <script>
        function resetXPDefaults() {
            if (confirm("Are you sure you want to reset all XP settings to default values?")) {
                document.getElementById("quiz_base_xp").value = 100;
                document.getElementById("exercise_base_xp").value = 50;
                document.getElementById("forum_post_xp").value = 10;
                document.getElementById("grade_multiplier").value = 2.0;
                document.getElementById("badge_bronze_xp").value = 50;
                document.getElementById("badge_silver_xp").value = 100;
                document.getElementById("badge_gold_xp").value = 200;
                document.getElementById("badge_platinum_xp").value = 500;
            }
        }
        </script>';
    }
}
