<?php
/**
 * @file
 * Provides uiplog view fields and filters.
 */

/**
 * Implements hook_views_data().
 */
function uiplog_views_data() {
  $data['uiplog']['table']['group'] = t('User IP');
  $data['uiplog']['table']['base'] = array(
    'field' => 'uid',
    'title' => t('User IP table'),
    'help' => t('Maps to uid in user table.'),
    'weight' => -10,
  );
  $data['uiplog']['table']['join'] = array(
    'users_field_data' => array(
      'left_field' => 'uid',
      'field' => 'uid',
    ),
  );
  // uid field
  $data['uiplog']['uid'] = array(
    'title' => t('User id'),
    'help' => t('Unique id of user'),
    'field' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'argument' => array(
      'id' => 'string',
    ),
    'relationship' => array(
      'title'              => t('User who logged in'),
      'help'               => t('The user associated with the login record.'),
      'id'                 => 'standard',
      'base'               => 'users_field_data',
      'base field'         => 'uid',
      'field'              => 'uid',
      'label'              => t('User who logged in'),
    ),
  );
  // IP field
  $data['uiplog']['ip'] = array(
    'title' => t('IP Address'),
    'help' => t('IP address of user.'),
    'field' => array(
      'id' => 'standard',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'argument' => array(
      'id' => 'string',
    ),
  );
  // timestamp field.
  $data['uiplog']['timestamp'] = array(
    'title' => t('Login Timestamp'),
    'help' => t('Time of login.'),
    'field' => array(
      'id' => 'date',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'id' => 'date',
    ),
    'filter' => array(
      'id' => 'date',
    ),
    'argument' => array(
      'id' => 'string',
    ),
  );
  return $data;
}
