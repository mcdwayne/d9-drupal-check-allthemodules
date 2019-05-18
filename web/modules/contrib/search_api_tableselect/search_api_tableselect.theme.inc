<?php

/**
 * @file
 * Preprocessors and helper functions to make theming easier.
 */

/**
 * Prepares variables for views table templates.
 *
 * Default template: views-view-table.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - view: A ViewExecutable object.
 *   - rows: The raw row data.
 */
function template_preprocess_views_view_tableselect_form(array &$variables) {
  $variables = [
    'view' => $variables['form']['#view'],
    'rows' => $variables['form']['#rows'],
    'form' => $variables['form'],
  ];
  \Drupal::moduleHandler()->loadInclude('views', 'inc', 'views.theme');
  template_preprocess_views_view_table($variables);
  foreach ($variables['rows'] as $delta => &$row) {
    $row['item_id'] = $variables['result'][$delta]->search_api_id;
  }
}
