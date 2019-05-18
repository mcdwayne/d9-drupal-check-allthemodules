<?php

/**
 * @file
 * Contains \Drupal\entity_reference_exposed_filters\entity_reference_exposed_filters.views.inc.
 */

/**
 * Implements hook_views_data_alter().
 */
function merci_line_item_views_data_alter(array &$data) {
  $data['merci_line_item_field_data']['eref_node_titles'] = [
    'title' => t('Entity Reference Exposed Filters Node Titles'),
    'filter' => [
      'title' => t('Entity Reference Exposed Filters Node Titles'),
      'help' => t('Show Enity Reference Node Titles as a list.'),
      'field' => 'id',
      'id' => 'eref_node_titles',
    ],
  ];
}
