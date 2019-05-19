<?php

/**
 * @file
 * Contains \Drupal\entity_reference_exposed_filters\entity_reference_exposed_filters.views.inc.
 */

/**
 * Implements hook_views_data_alter().
 */
function views_dependent_filter_views_data_alter(array &$data) {
  $data['views']['views_dependent_filter'] = [
    'title' => t('Dependent filter'),
    'filter' => [
      'title' => t('Dependent filter'),
      'help' => t('Does not provide a filter itself but allows the values of one exposed filter to control the presence of other filters.'),
      'field' => 'nid',
      'id' => 'views_dependent_filter',
    ],
  ];
}
