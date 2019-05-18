<?php

/**
 * @file
 * Views hook implementations for the Flag Search API module.
 */

use Drupal\search_api\Entity\Index;

/**
 * Implements hook_views_data_alter().
 */
function flag_search_api_views_data_alter(array &$data) {
  $flagService = \Drupal::service('flag');
  $flags = $flagService->getAllFlags();

  /** @var \Drupal\search_api\IndexInterface $index */
  foreach (Index::loadMultiple() as $index) {
    $table = &$data['search_api_index_' . $index->id()];

    /** @var \Drupal\search_api\Item\FieldInterface $field */
    foreach ($index->getFields(TRUE) as $field_id => $field) {
      foreach ($flags as $flag_id => $flag) {
        if ($field->getFieldIdentifier() == 'flag_' . $flag_id) {
          $table['flag_' . $flag_id]['field']['id'] = 'search_api_flag';
        }
      }
    }
  }
}
