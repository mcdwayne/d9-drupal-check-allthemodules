<?php

/**
 * @file
 * Role Expire Views hooks.
 *
 * References:
 * https://www.webomelette.com/creating-custom-views-field-drupal-8
 * https://drupalium.com/articles/creating-custom-view-fields-programmatically
 * https://api.drupal.org/api/drupal/core%21modules%21views%21views.api.php/function/hook_views_data/8.6.x
 */

/**
 * Implements hook_views_data().
 *
 * @return array
 */
function role_expire_views_data() {

  $data = [];
  $data['role_expire'] = [];
  $data['role_expire']['table'] = [];

  $data['role_expire']['table']['group'] = t('Role expire');

  $data['role_expire']['table']['provider'] = 'role_expire';

  $data['role_expire']['table']['join'] = [
    'users_field_data' => [
      'left_field' => 'uid',
      'field' => 'uid',
    ],
  ];

  // Integer timestamp field, exposed as a field, sort, and filter.
  $data['role_expire']['expiry_timestamp'] = [
    'title' => t('Role expiration date/time'),
    'help' => t('Date and time the role will expire. (See also Role expiration role.'),
    'field' => [
      // ID of field handler plugin to use.
      'id' => 'date',
    ],
    'sort' => [
      // ID of sort handler plugin to use.
      'id' => 'date',
    ],
    'filter' => [
      // ID of filter handler plugin to use.
      'id' => 'date',
    ],
  ];

  $data['role_expire']['rid'] = [
    'title' => t('Role expiration role'),
    'help' => t('The Role that corresponds with the Role expiration date/time'),
    'field' => [
      // ID of field handler plugin to use.
      'id' => 'standard',
    ],
    'sort' => [
      // ID of sort handler plugin to use.
      'id' => 'standard',
    ],
    'filter' => [
      // ID of filter handler plugin to use.
      'id' => 'string',
    ],
  ];

  return $data;
}

/**
 * Implements hook_views_data_alter().
 */
function role_expire_views_data_alter(array &$data) {
  // Extend the users table to add the role expiration data (a simple field to render data).
  $data['users']['role_expire_expiry_data'] = [
    'title' => t('Role expiration all data'),
    'group' => t('Role expire'),
    'field' => [
      'title' => t('Role expire: Role expiration data'),
      'help' => t('List of roles that will expire together with their date and time of expiration.'),
      // ID of the views plugin that will handle the field.
      'id' => 'role_expire_expiry_data',
   ],
  ];
}
