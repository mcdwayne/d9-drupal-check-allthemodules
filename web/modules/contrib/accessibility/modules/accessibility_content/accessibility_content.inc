<?php

function accessibility_content_load_tests_json() {
  $tests = _accessibility_content_get_quail_tests();
  $result = array();
  $existing_tests = accessibility_get_active_tests();
  foreach ($tests as $test_name => $test) {
    if (isset($existing_tests[$test_name])) {
      $test->accessibility_content_test_id = $existing_tests[$test_name];
      $result[$test_name] = $test;
    }
  }
  drupal_json_output(array('guideline' => array_keys($result), 'tests' => $result));
}

function accessibility_content_init($options = array()) {
  static $init;
  if ($init) {
    return;
  }
  $init = TRUE;
  if (!user_access('check content for accessibility')) {
    return;
  }
  $config = Drupal::config('accessibility.accessibility_content');

  $settings = array();
  $settings['show_default'] = $config->get('auto_check');
  $settings['show_toggle'] = $config->get('show_toggle');
  $settings['toggle']['off_message'] = ($config->get('toggle_message_off')) ? $config->get('toggle_message_off') : t('Check page for accessibility');
  $settings['toggle']['on_message'] = ($config->get('toggle_message_on')) ? $config->get('toggle_message_on') : t('Hide checks for accessibility');
  $settings['options'] = $options;
  
  accessibility_load();
  drupal_add_js(array('accessibility_content' => $settings), 'setting');
  drupal_add_js(drupal_get_path('module', 'accessibility_content') . '/js/accessibility_content.js');
  drupal_add_css(drupal_get_path('module', 'accessibility_content') . '/css/accessibility_content.css');
}

function _accessibility_content_field_pre_render($element) {
  $element['#prefix'] = (isset($element['#prefix'])) ? $element['#prefix'] : '';
  $element['#suffix'] = (isset($element['#suffix'])) ? $element['#suffix'] : '';
  $element['#prefix'] = '<span class="ac-check-field">' . $element['#prefix'];
  $element['#suffix'] .= '</span>';
  return $element;
}