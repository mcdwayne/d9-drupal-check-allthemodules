<?php

/**
 * @file
 * Views interface for Spain LOPD.
 */

function lopd_views_data() {
  $data = array();
  $data['lopd']['table'] = array(
    'group' => t('LOPD'),
  );
  $data['lopd']['table']['base'] = array(
    'field' => 'lopdid',
    'title' => 'LOPD registers',
    'weight' => 10,
    'help' => t('Shows LOPD operations registered.'),
  );

  // This table references the {user} table.
  // This creates an 'implicit' relationship to the node table, so that when 'User'
  // is the base table, the fields are automatically available.
  $data['lopd']['table']['join'] = array(
    'users' => array(
      'left_field' => 'uid',
      'field' => 'uid',
    ),
  );

  // Next, describe each of the individual fields in this table to Views. For
  // each field, you may define what field, sort, argument, and/or filter
  // handlers it supports. This will determine where in the Views interface you
  // may use the field.
  $data['lopd']['lopdid'] = array(
    'title' => t('LOPD ID'),
    'help' => t('The LOPD ID of the register.'), // The help that appears on the UI,
    // Information for displaying the lopdid
    'field' => array(
      'id' => 'numeric',
    ),
    // Information for accepting a lopdid as a filter
    'filter' => array(
      'id' => 'numeric',
    ),
    // Information for sorting on a lopdid.
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['lopd']['uid'] = array(
    'title' => t('User'),
    'help' => t('User that did the operation.'),
    'relationship' => array(
      'title' => t('User'),
      'help' => t('The user on which the log entry as written.'),
      'base' => 'users',
      'base field' => 'uid',
      'id' => 'standard',
    ),
  );
  $data['lopd']['authname'] = array(
    'title' => t('Name'),
    'help' => t('Authentication name that did this operation.'),
    'field' => array(
      'id' => 'standard',
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
  $data['lopd']['ip'] = array(
    'title' => t('IP'),
    'help' => t('IP address that did this operation.'),
    'field' => array(
      'id' => 'standard',
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
  $data['lopd']['operation'] = array(
    'title' => t('Operation'),
    'help' => t('Operation that has been registered.'),
    'field' => array(
      'id' => 'standard',
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
  $data['lopd']['timestamp'] = array(
    'title' => t('Timestamp'),
    'help' => t('The timestamp where the operation was registered.'),
    'field' => array(
      'id' => 'date',
    ),
    'argument' => array(
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
