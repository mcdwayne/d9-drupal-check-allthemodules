<?php

/**
 * @file
 * Views Fields On/Off views code.
 */

/**
 * Implements hook_views_data().
 */
function views_fields_on_off_views_data() {
  $data['views']['views_fields_on_off_form'] = [
    'title' => t('On/Off Form'),
    'help' => t('Display fields to be switched on and off.'),
    'field' => [
      'id' => 'views_fields_on_off_form',
    ],
  ];

  return $data;
}
