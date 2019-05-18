<?php

/**
 * @file
 * Provide views data for campaign monitor subscriptions stored locally.
 */

/**
 * Implements hook_views_data().
 */
function campaignmonitor_local_views_data() {
  // Applying to group auto joins the table for views.
  $data['campaignmonitor_local_subscriptions']['table']['group'] = t('User');
  $data['campaignmonitor_local_subscriptions']['table']['provider'] = 'user';
  $data['campaignmonitor_local_subscriptions']['table']['entity_type'] = 'user';

  $data['campaignmonitor_local_subscriptions']['table']['join'] = ['users_field_data' => ['left_field' => 'uid', 'field' => 'uid']];

  $data['campaignmonitor_local_subscriptions']['list_id'] = [
    'title' => t('Lists'),
    'help' => t('Lists that a user belongs to.'),
    'field' => ['id' => 'campaignmonitor_local_lists', 'no group by' => TRUE],
    'filter' => ['id' => 'standard'],
    'argument' => ['id' => 'standard'],
    'sort' => ['id' => 'standard']
  ];

  $data['campaignmonitor_local_subscriptions']['updated'] = [
    'title' => t('Updated'),
    'help' => t('The time when the record was updated.'),
    'field' => ['id' => 'date', 'click sortable' => TRUE],
    'filter' => ['id' => 'date'],
    'argument' => ['id' => 'date'],
    'sort' => ['id' => 'standard']
  ];

  $data['campaignmonitor_local_lists']['table']['group'] = t('Campaign Monitor');

  $data['campaignmonitor_local_lists']['table']['join'] = ['campaignmonitor_local_subscribers' => ['left_field' => 'list_id', 'field' => 'list_id']];

  $data['campaignmonitor_local_subscriptions']['table']['join'] = ['campaignmonitor_local_lists' => ['left_field' => 'list_id', 'field' => 'list_id']];

  return $data;
}

/**
 * Implements hook_views_data_alter().
 */
function campaignmonitor_local_views_data_alter(&$data) {
  $data['users_field_data']['uid_subscriptions'] = [
    'real field' => 'uid',
    'title' => t('Campaign Monitor subscriptions'),
    'help' => t('Display CM subscriptions.'),
    'field' => ['id' => 'campaignmonitor_local_lists'],
  ];
}
