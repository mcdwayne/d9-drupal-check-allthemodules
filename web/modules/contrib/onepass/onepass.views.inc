<?php

/**
 * @file
 * Provides views data for onepass.module.
 */

/**
 * Implements hook_views_data_alter().
 */
function onepass_views_data_alter(&$data) {
  $data['node']['onepass_relation'] = array(
    'title' => t('OnePass relation'),
    'help' => t('Relation to OnePass service marked nodes.'),
    'relationship' => array(
      'id' => 'standard',
      'base' => 'onepass_node',
      'base field' => 'nid',
      'field' => 'nid',
      'label' => t('OnePass relation'),
      'title' => t('OnePass relation'),
      'help' => t('Relation to OnePass service marked nodes.'),
    ),
  );
}
