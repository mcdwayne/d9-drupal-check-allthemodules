<?php

use Drupal\Core\Render\Element;
/**
 * @file
 * Template, theme, and preprocess functions for the flot_views_time mosule.
 */

/**
 * Implements template_preprocess_TEMPLATE().
 */
function template_preprocess_views_view_flot_views_time(&$variables) {
  // View options set by user.
  $options = $variables['view']->style_plugin->options;

  // Initialize the data variable.
  $data = [];
  $view = $variables['view'];

  foreach ($options['columns']['x'] as $id => $values) {
    $series = [];
    // The actual x-y values for the one series.
    $values = [];
    $x_column = $options['columns']['x'][$id];
    $y_column = $options['columns']['y'][$id];
    if ($options['info'][$id]['points'] == 1) {
      $series['points'] = ['show' => TRUE];
    }
    if ($options['info'][$id]['lines'] == 1) {
      $series['lines'] = ['show' => TRUE];
    }
    foreach ($view->result as $row) {
      $datetime_text = $view->field[$x_column]->getValue($row);

      // Sometimes datetimes are unix timestamps, and some times they are
      // ISO strings. There has to be a better way to do this.
      $datetime_stringed_int = '' . intval($datetime_text);
      //If these are equal, we had an int in the database.
      if ($datetime_text == $datetime_stringed_int) {
        $datetime_text = '@' . $datetime_text;
      }
      $datetime_object = new DateTime($datetime_text, new DateTimeZone('UTC'));
      $datetime_object->setTimezone(new DateTimeZone('America/New_York'));

      $unix_time = $datetime_object->getTimestamp();
      $value = $view->field[$y_column]->getValue($row);
      if ($view->field[$y_column]->multiple) {
        $value = $view->field[$y_column]->getItems($row)[0]['rendered']['#markup'];
      }
      $values[] = [
        // Convert unix time to JS time (seconds to milliseconds)
        $unix_time * 1000,
        $value,
      ];
    }
    if ($options['info'][$id]['second_axis'] == 1) {
      $series['yaxis'] = 2;
    }
    $series['label'] = $y_column;
    $series['data'] = $values;
    $data[] = $series;
  }
  // Update options for twig.
  $flot_options = [
    'xaxis' => ['mode' => 'time'],
  ];
  $variables['flot_views_time'] = [
    '#type' => 'flot',
    '#data' => $data,
    '#options' => $flot_options,
  ];
}

/**
 * Implements theme_TEMPLATE().
 */
function theme_views_view_flot_views_time($variables) {
  $output = [
    'flot_views_time' => $variables['flot_views_time'],
  ];
  return drupal_render($output);
}

function template_preprocess_views_ui_style_plugin_flot_views_table(&$variables) {
  $form = $variables['form'];

  $header = array(
    t('Series'),
    t('X Field'),
    t('Y Field'),
    t('Points'),
    t('Lines'),
    t('2nd Axis'),
  );
  $rows = array();
  foreach (Element::children($form['columns']['x']) as $id) {
    $row = array();
    $row[]['data'] = $id;
    $row[]['data'] = $form['columns']['x'][$id];
    $row[]['data'] = $form['columns']['y'][$id];
    $row[]['data'] = $form['info'][$id]['points'];
    $row[]['data'] = $form['info'][$id]['lines'];
    $row[]['data'] = $form['info'][$id]['second_axis'];
    $rows[] = $row;
  }

  // Add the special 'None' row.
//  $rows[] = array(array('data' => t('None'), 'colspan' => 6), array('align' => 'center', 'data' => $form['default'][-1]), array('colspan' => 2));

  // Unset elements from the form array that are used to build the table so that
  // they are not rendered twice.
//  unset($form['default']);
  unset($form['info']);
  unset($form['columns']);

  $variables['table'] = array(
    '#type' => 'table',
    '#theme' => 'table__views_ui_style_plugin_flot_views_table',
    '#header' => $header,
    '#rows' => $rows,
  );
  $variables['form'] = $form;
}
