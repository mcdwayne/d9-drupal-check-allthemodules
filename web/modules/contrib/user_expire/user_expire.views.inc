<?php
/**
 * @file
 * Views integration for the User Expire module.
 */

/**
 * Implements hook_views_data().
 */
function user_expire_views_data() {
  $data['user_expire']['table']['group'] = t('User');

  $data['user_expire']['table']['join'] = array(
    'users_field_data' => array(
      'left_field' => 'uid',
      'field' => 'uid',
    ),
  );

  $data['user_expire']['expiration'] = array(
    'title' => t('Expiration date'),
    'help' => t('The date on which this account will be disabled.'),
    'field' => array(
      'id' => 'date',
    ),
    'filter' => array(
      'id' => 'date',
    ),
    'sort' => array(
      'id' => 'date',
    ),
  );

  return $data;
}
