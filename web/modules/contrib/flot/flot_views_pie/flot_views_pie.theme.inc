<?php

/**
 * @file
 * Template theme and preprocess functions for the flot_views_pie module.
 */

/**
 * Implements template_preprocess_TEMPLATE().
 */
function template_preprocess_views_view_flot_views_pie(&$variables) {
  // View options set by user.
  $options = $variables['view']->style_plugin->options;
  $data = [];
  $series = [];
  $flot_options = [];
  $view = $variables['view'];
  $label_column = $options['labels'];
  $value_column = $options['values'];
  $pie_or_bar = $options['pie_or_bar'];
  if ($pie_or_bar == 'pie'){
    foreach ($view->result as $row) {
      $label = $view->field[$label_column]->advancedRender($row);
      $value = $view->field[$value_column]->getValue($row);
      $series = [
        'label' => $label,
        'data'  => $value,
      ];

      // In a pie chart, each slice is a series.
      $data[] = $series;
    }
    $flot_options = ['series' => ['pie' => ['show' => TRUE]]];
  }
  else {
    foreach ($view->result as $row) {
      $label = $view->field[$label_column]->advancedRender($row);
      $value = $view->field[$value_column]->getValue($row);
      $series[] = [$label, $value];
    }

    // In a bar chart, you can have multiple datapoints in multiple series.
    $data[] = $series;
    $flot_options = [
      'series' => [
        'bars' => ['show' => TRUE, 'barWidth' => .6, 'align' => 'center'],
      ],
      'xaxis' => ['mode' => 'categories', 'tickLength' => 0],
    ];
  }

  // Update options for twig.
  $variables['flot_views_pie'] = [
    '#type' => 'flot',
    '#data' => $data,
    '#options' => $flot_options,
  ];
}

/**
 * Implements theme_TEMPLATE().
 */
function theme_views_view_flot_views_pie($variables) {
  $output = [
    'flot_views_pie' => $variables['flot_views_pie'],
  ];
  return drupal_render($output);
}
