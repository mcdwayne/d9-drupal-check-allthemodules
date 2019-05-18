<?php

/**
 * @file
 * Preprocessors and theme functions for the CARTO Sync.
 */

use Drupal\Core\Render\Element;
use Drupal\Core\Template\Attribute;

/**
 * Prepares variables for style plugin table templates.
 *
 * Default template: carto-sync-style-plugin-table.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - form: A render element representing the form.
 */
function template_preprocess_carto_sync_style_plugin_table(&$variables) {
  $form = $variables['form'];

  $header = [
    t('Field'),
    t('CARTO Column'),
    t('Primary Key'),
    t('The Geom'),
  ];
  $rows = [];
  foreach (Element::children($form['info']) as $id) {
    $row = [];
    $row[]['data'] = $form['info'][$id]['name'];
    $row[]['data'] = $form['info'][$id]['field_name'];

    if (!empty($form['primary_key'][$id])) {
      $row[] = [
        'data' => $form['primary_key'][$id],
        'align' => 'center',
      ];
    }
    else {
      $row[] = t('N/A');
    }

    if (!empty($form['the_geom'][$id])) {
      $row[] = [
        'data' => $form['the_geom'][$id],
        'align' => 'center',
      ];
    }
    else {
      $row[] = t('N/A');
    }

    $rows[] = $row;
  }

  // Unset elements from the form array that are used to build the table so that
  // they are not rendered twice.
  unset($form['primary_key']);
  unset($form['the_geom']);
  unset($form['info']);
  unset($form['columns']);

  $variables['table'] = [
    '#type' => 'table',
    '#theme' => 'table__carto_sync_style_plugin_table',
    '#header' => $header,
    '#rows' => $rows,
  ];
  $variables['form'] = $form;
}

/**
 * Prepares variables for Views UI view listing templates.
 *
 * Default template: views-ui-view-listing-table.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - headers: An associative array containing the headers for the view
 *     listing table.
 *   - rows: An associative array containing the rows data for the view
 *     listing table.
 */
function template_preprocess_carto_sync_listing_table(&$variables) {
  // Convert the attributes to valid attribute objects.
  foreach ($variables['headers'] as $key => $header) {
    $variables['headers'][$key]['attributes'] = new Attribute($header['#attributes']);
  }

  if (!empty($variables['rows'])) {
    foreach ($variables['rows'] as $key => $row) {
      if (isset($row['#attributes'])) {
        $variables['rows'][$key]['attributes'] = new Attribute($row['#attributes']);
      }
    }
  }
}
