<?php

/**
 * @file
 * Views hooks implemented for the Mailman Integration module.
 */

/**
 * Implements hook_views_data().
 */
function mailman_integration_views_data() {
  $data = array();
  // Mailman Lists.
  $data['mailman_integration_list']['table']['group'] = t('Mailman Mailing Lists');
  $data['mailman_integration_list']['table']['base'] = array(
    'field' => 'list_id',
    'title' => t('Mailman Mail Lists'),
    'help'  => t('Mailman Mail List By Mailman Integration module'),
  );

  $data['mailman_integration_list']['table']['join'] = array(
    'mailman_list_users' => array(
      'left_field' => 'list_id',
      'field' => 'list_id',
    ),
  );

  $data['mailman_integration_list']['list_id'] = array(
    'title' => t('List ID'),
    'help'  => t('Mailman List Id'),
    'field' => array(
      'id' => 'numeric',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'argument' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['mailman_integration_list']['list_name'] = array(
    'title' => t('Mailman List Name'),
    'help'  => t('Mailman List Name'),
    'field' => array(
      'id' => 'standard',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'id' => 'string',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['mailman_integration_list']['list_owners'] = array(
    'title' => t('Mailman Owners email'),
    'help'  => t('Mailman Owners email'),
    'field' => array(
      'id' => 'standard',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'id' => 'string',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['mailman_integration_list']['bundle'] = array(
    'title' => t('Mailman List Type'),
    'help'  => t('Mailman List Type, manual/role'),
    'field' => array(
      'id' => 'standard',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'id' => 'string',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['mailman_integration_list']['description'] = array(
    'title' => t('Mailman List Description'),
    'help'  => t('Mailman List Description'),
    'field' => array(
      'id' => 'standard',
    ),
    'argument' => array(
      'id' => 'string',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['mailman_integration_list']['visible_to_user'] = array(
    'title' => t('User can Subscribe'),
    'help' => t('User can Subscribe this List'),
    'field' => array(
      'id' => 'numeric',
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'argument' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['mailman_integration_list']['created'] = array(
    'title' => t('List created date time'),
    'help' => t('List created date time'),
    'field' => array(
      'id' => 'date',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'id' => 'date',
    ),
    'argument' => array(
      'id' => 'date',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );
  // Mailman Subscribers.
  $data['mailman_list_users']['table']['group'] = t('Mailman Mailing List Subscribers');
  $data['mailman_list_users']['table']['join'] = array(
    'mailman_integration_list' => array(
      'left_field' => 'list_id',
      'field' => 'list_id',
      'type' => 'INNER',
    ),
  );

  $data['mailman_list_users']['uid'] = array(
    'title' => t('Subscribers User Id'),
    'help' => t('Subscribers uid'),
    'field' => array(
      'id' => 'numeric',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'argument' => array(
      'id' => 'numeric',
    ),
    'relationship' => array(
      'title' => t('User'),
      'help' => t('Drupal User Relationship.'),
      'base' => 'users_field_data',
      'base field' => 'uid',
      'id' => 'standard',
    ),
  );

  $data['mailman_list_users']['list_id'] = array(
    'title' => t('List Id'),
    'help'  => t('Mailman Custom table List Id'),
    'field' => array(
      'id' => 'numeric',
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'argument' => array(
      'id' => 'numeric',
    ),
    'relationship' => array(
      'title' => t('Mail List'),
      'help' => t('Mailman List Relationship.'),
      'base' => 'mailman_integration_list',
      'base field' => 'list_id',
      'id' => 'standard',
    ),
  );

  $data['mailman_list_users']['mail'] = array(
    'title' => t('Subscribers email'),
    'help'  => t('Subscribers email'),
    'field' => array(
      'id' => 'standard',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'id' => 'string',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['mailman_list_users']['created'] = array(
    'title' => t('Subscribe date time'),
    'help'  => t('Subscribe date time'),
    'field' => array(
      'id' => 'date',
    ),
    'filter' => array(
      'id' => 'date',
    ),
    'argument' => array(
      'id' => 'date',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );
  return $data;
}
