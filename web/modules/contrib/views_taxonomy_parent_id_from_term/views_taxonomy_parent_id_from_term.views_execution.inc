<?php

/**
 * @file
 * Alter the query for data added by views_taxonomy_parent_id_from_term module.
 */

use Drupal\views\ViewExecutable;

/**
 * Implements hook_views_query_alter().
 */
function views_taxonomy_parent_id_from_term_views_query_alter(ViewExecutable $view, $query) {
  if (isset($query->where)) {
    foreach ($query->where as &$condition_group) {
      foreach ($condition_group['conditions'] as &$condition) {
        if (substr_count($condition['field'], ':taxonomy_term__parent_term_parent')) {
          $storage = \Drupal::service('entity_type.manager')->getStorage('taxonomy_term');
          $parents = $storage->loadParents($condition['value'][':taxonomy_term__parent_term_parent']);

          reset($parents);
          $first_key = key($parents);

          if ($first_key) {
            $parent_id = $parents[$first_key]->id();
          }
          else {
            $parent_id = NULL;
          }

          $condition['value'][':taxonomy_term__parent_term_parent'] = $parent_id;
        }
      }
    }
  }
}
