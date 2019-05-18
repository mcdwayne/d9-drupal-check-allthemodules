<?php

/**
 * @file
 * Provide views data for httpbl.module.
 */

/**
 * Implements hook_views_data_alter().
 */
function httpbl_views_data_alter(&$data) {

  // Expose core Ban_ip table to Views so we can access for relationships with
  // banned hosts (defined in class HostViewsData).
  
  // Base data.
  $data['ban_ip']['table']['group'] = t('Ban');
  $data['ban_ip']['table']['provider'] = 'ban';
  $data['ban_ip']['table']['base'] = [
    'field' => t('iid'),
    'title' => t('Ban IP'),
    'help' => t('Used by core Ban module.'),
    'query_id' => 'views_query',
  ];

  // Fields.
  $data['ban_ip']['iid'] = [
    'title' => t('Ban id'),
    'help' => t('Ban_ip records id.'),
    'field' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'argument' => [
      'id' => 'numeric',
    ],
  ];
  $data['ban_ip']['ip'] = [
    'title' => t('Ban ip'),
    'help' => t('IP address of a banned record'),
    'field' => [
      'id' => 'standard',
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'argument' => [
      'id' => 'string',
    ],
  ];

}