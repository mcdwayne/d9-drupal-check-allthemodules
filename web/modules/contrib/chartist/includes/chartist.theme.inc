<?php

/**
 * @file
 * Theme functions for charts.
 */

/**
 * Attach chartist library files.
 */
function template_preprocess_chartist(&$variables) {
  static $chart_no = 0;
  $chart_no++;
  $variables['chart_id'] = 'chartist-' . $chart_no;

  $settings[$chart_id] = array(
    'type' => $vars['chart_type'],
    'data' => $data,
  );
  if (isset($vars['settings'])) {
    $settings[$chart_id]['settings'] = $vars['settings'];
  }
  else {
    $settings[$chart_id]['settings'] = array();
  }

  $variables['#attached']['drupalSettings'] = $settings;
  $variables['#attached']['library'][] = 'chartist';
}

/**
 * Chartist theme.
 */
function theme_chartist(&$vars) {
  $chart_id = $variables['chart_id'];

  if (empty($vars['data'])) {
    return t('No data provided.');
  }

  $data = $vars['data'];

  // Sanitize data if html is not set to TRUE.
  if (empty($vars['html'])) {

    // Chart title.
    if (!empty($vars['title'])) {
      $vars['title'] = check_plain($vars['title']);
    }
    // Chart labels.
    if (!empty($data['labels'])) {
      foreach ($data['labels'] as $key => $label) {
        $data['labels'][$key] = check_plain($label);
      }
    }
    // Serie names.
    foreach ($data['series'] as $key => $serie) {
      if (is_array($serie) && isset($serie['name'])) {
        $data['series'][$key]['name'] = check_plain($serie['name']);
      }
    }
  }

  // Element classes.
  $classes = array('chartist');
  if (!empty($vars['classes'])) {
    if (is_string($vars['classes'])) {
      $vars['classes'] = array($vars['classes']);
    }

    // Sanitize classes.
    foreach ($vars['classes'] as $index => $class) {
      $vars['classes'][$index] = drupal_html_class($class);
    }
    $classes = array_merge($classes, $vars['classes']);
  }

  if (!empty($vars['wrapper_class'])) {
    $wrapper_classes = array($vars['wrapper_class'], 'chart-wrapper');
  }
  else {
    $wrapper_classes = array('chart-wrapper');
  }

  // On / off checkboxes.
  if (!empty($vars['settings']['onoff'])) {
    $class_letters = 'abcdefghijklmnopqrstuvwzyz';
    $index = 0;

    $onoff = '<div class="chartist-series-onoff" for="' . $chart_id . '">';
    foreach ($data['series'] as $serie) {
      $class = 'ct-series-' . ($class_letters[$index]);
      $id = $chart_id . '-' . $class_letters[$index] . '-switch';
      $onoff .= '<span class="' . $class . '">';
      $onoff .= '<input type="checkbox" data-serie=".' . $class . '" id="' . $id . '" checked="checked">';
      $onoff .= '<label for="' . $id . '">' . $serie['name'] . '</label>';
      $onoff .= '</span>';
      $index++;
    }
    $onoff .= '</div>';
  }

  $output = '';
  if (!empty($vars['element_prefix'])) {
    $output .= $vars['element_prefix'];
  }
  $output .= '<div class="' . implode(' ', $wrapper_classes) . '">';
  if (!empty($vars['title'])) {
    $output .= '<h3>' . $vars['title'] . '</h3>';
  }
  $output .= '<div class="chart-wrapper">';
  $output .= '<div id="' . $chart_id . '" class="' . implode(' ', $classes) . '"></div>';
  $output .= '</div>';

  if (!empty($vars['settings']['onoff']) && $vars['settings']['onoff'] != 'external') {
    $output .= $onoff;
  }

  $output .= '</div>';

  if (!empty($vars['element_suffix'])) {
    $output .= $vars['element_suffix'];
  }

  if (!empty($vars['settings']['onoff']) && $vars['settings']['onoff'] === 'external') {
    $output .= $onoff;
  }

  return $output;
}
