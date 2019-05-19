<?php

/**
* @file
* Provide views data for the white label module.
**/

/**
 * Implements hook_views_data_alter().
 */
function whitelabel_views_data_alter(array &$data) {
  $data['users']['uid'] = [
    'title' => t('White Label'),
    'help' => t('White Label associated with the user'),
    'relationship' => [
      'id' => 'standard',
      'base' => 'whitelabel_field_data',
      'base field' => 'uid',
      'label' => t('White Label associated with the user'),
    ],
  ];
  return $data;
}
