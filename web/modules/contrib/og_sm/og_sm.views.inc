<?php

/**
 * @file
 * Views hooks.
 */

use Drupal\field\FieldStorageConfigInterface;

/**
 * Implements hook_field_views_data_alter().
 */
function og_sm_field_views_data_alter(array &$data, FieldStorageConfigInterface $field_storage) {
  if ($field_storage->getType() !== 'og_standard_reference') {
    return;
  }

  foreach ($data as $table_name => $table_data) {
    $field_name = $field_storage->getName();
    if (!isset($table_data[$field_name])) {
      continue;
    }
    $relationship_handler = $data[$table_name][$field_name]['relationship'];

    $data[$table_name]['og_sm_sites'] = [
      'title' => t('Site'),
      'group' => $relationship_handler['group'],
      'help' => t('Filter on the site linked to the entity.'),
      'filter' => [
        'id' => 'og_sm_sites',
        'field' => 'og_audience_target_id',
      ],
    ];
    $data[$table_name]['og_sm_manageable_sites_by_current_user'] = [
      'title' => t('Manageable sites by current user'),
      'group' => $relationship_handler['group'],
      'help' => t('Filter on all sites the current user can manage.'),
      'filter' => [
        'id' => 'og_sm_manageable_sites_by_current_user',
        'field' => 'og_audience_target_id',
        'type' => 'yes-no',
      ],
    ];
  }
}
