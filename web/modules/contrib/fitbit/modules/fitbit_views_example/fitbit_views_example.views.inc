<?php
/**
 * @file
 *
 * Views will look in this file for any views related hooks. Mainly, this is
 * where you put your hook_views_data() implementation.
 */


/**
 * Implements hook_views_data().
 */
function fitbit_views_example_views_data() {
  $data = [];

  // Base data.
  $data['fitbit_profile']['table']['group'] = t('Fitbit profile');
  $data['fitbit_profile']['table']['base'] = [
    'title' => t('Fitbit profile'),
    'query_id' => 'fitbit',
    'help' => t('Fitbit profile data provided by the Fitbit API\'s User Profile endpoint.'),
  ];

  // Fields.
  $data['fitbit_profile']['display_name'] = [
    'title' => t('Display name'),
    'help' => t('Fitbit users\' display name.'),
    'field' => [
      'id' => 'standard',
    ],
  ];
  $data['fitbit_profile']['average_daily_steps'] = [
    'title' => t('Average daily steps'),
    'help' => t('The average daily steps over all the users logged Fitbit data.'),
    'field' => [
      'id' => 'numeric',
    ],
  ];
  $data['fitbit_profile']['avatar'] = [
    'title' => t('Avatar'),
    'help' => t('Fitbit users\' account picture.'),
    'field' => [
      'id' => 'fitbit_avatar',
    ],
  ];
  $data['fitbit_profile']['height'] = [
    'title' => t('Height'),
    'help' => t('Fibit users\'s height.'),
    'field' => [
      'id' => 'numeric',
      'float' => TRUE,
    ],
  ];
  $data['fitbit_profile']['uid'] = [
    'title' => t('User id'),
    'help' => t('Drupal user id, not to be confused with Fitbit profile id.'),
    'field' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'fitbit_uid',
    ],
  ];

  return $data;
}