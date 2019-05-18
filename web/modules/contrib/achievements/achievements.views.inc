<?php

/**
 * @file
 */

/**
 * Implements hook_views_data().
 */
function achievements_views_data() {
  $data = [];

  $join = [
    'users_field_data' => [
      'type' => 'INNER',
      'left_field' => 'uid',
      'left_table' => 'users_field_data',
      'field' => 'uid',
    ],
  ];
  $achievement_id = [
    'title' => t("Achievement ID"),
    'help' => t(''),
    'field' => [
      'id' => 'standard',
    ],
    'argument' => [
      'id' => 'achievement_id',
    ],
  ];
  $config = [
    'title' => t("Achievement Config"),
    'help' => t('Configuration for the achievement.'),
    'real field' => 'achievement_id',
    'field' => [
      'id' => 'achievement_config',
    ],
  ];
  $uid = [
    'title' => t('User ID'),
    'help' => t('The user ID of the achiever.'),
    'field' => [
      'id' => 'numeric',
    ],
    'argument' => [
      'id' => 'user_uid',
    ],
    'filter' => [
      'title' => t('User ID'),
      'id' => 'user_name',
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'relationship' => [
      'base' => 'users_field_data',
      'base field' => 'uid',
      'id' => 'standard',
      'label' => t('User'),
    ],
  ];
  $timestamp = [
    'title' => t('Timestamp'),
    'help' => t('The date of the achievement unlock.'),
    'field' => [
      'id' => 'date',
    ],
    'sort' => [
      'id' => 'date',
    ],
    'filter' => [
      'id' => 'date',
    ],
  ];

  $data['achievement_storage'] = [];
  $data['achievement_storage']['table'] = [];
  $data['achievement_storage']['table']['group'] = t('Achievements Storage');
  $data['achievement_storage']['table']['base'] = [
    'field' => 'uid',
    'title' => t('User achievement storage data'),
    'help' => t('This contains storage data that is used to typically only used implementations of the Achievements API.'),
    'weight' => -10,
  ];
  $data['achievement_storage']['table']['join'] = $join;
  $data['achievement_storage']['uid'] = $uid;
  $data['achievement_storage']['data'] = [
    'title' => t('Data'),
    'help' => t(''),
    'field' => [
      'id' => 'serialized',
    ],
  ];

  $data['achievement_totals'] = [];
  $data['achievement_totals']['table'] = [];
  $data['achievement_totals']['table']['group'] = t('Achievements Totals');
  $data['achievement_totals']['table']['base'] = [
    'field' => 'uid',
    'title' => t('User achievement totals'),
    'help' => t('Totals are used to create the Achievements leaderboard.'),
    'weight' => -10,
  ];
  $data['achievement_totals']['table']['join'] = $join;
  $data['achievement_totals']['uid'] = $uid;
  $data['achievement_totals']['timestamp'] = $timestamp;
  $data['achievement_totals']['config'] = $config;
  $data['achievement_totals']['achievement_id'] = $achievement_id;
  $data['achievement_totals']['achievement_id']['title'] = t('Latest achievement ID');
  $data['achievement_totals']['points'] = [
    'title' => t('Points'),
    'help' => t('The total points earned by the user.'),
    'field' => [
      'id' => 'numeric',
      'click sortable' => TRUE,
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
  $data['achievement_totals']['unlocks'] = [
    'title' => t('Unlocks'),
    'help' => t('The total number of achievements unlocked by the user.'),
    'field' => [
      'id' => 'numeric',
      'click sortable' => TRUE,
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
  $data['achievement_totals']['latest_achievement'] = [
    'title' => t('Latest achievement for user'),
    'help' => t('The most recent achievement for a given user.'),
    'real field' => 'uid',
    'field' => [
      'id' => 'latest_achievement',
    ],
    'relationship' => [
      'base' => 'users_field_data',
      'base field' => 'uid',
      'id' => 'standard',
      'label' => t('User'),
    ],
  ];

  $data['achievement_unlocks']                   = [];
  $data['achievement_unlocks']['table']          = [];
  $data['achievement_unlocks']['table']['group'] = t('Achievements Unlocks');
  $data['achievement_unlocks']['table']['base']  = [
    'field' => 'uid',
    'title' => t('User achievement unlocks'),
    'help' => t(''),
    'weight' => -10,
  ];
  $data['achievement_unlocks']['table']['join']  = $join;
  $data['achievement_unlocks']['config']         = $config;
  $data['achievement_unlocks']['achievement_id'] = $achievement_id;
  $data['achievement_unlocks']['uid']            = $uid;
  $data['achievement_unlocks']['timestamp']      = $timestamp;
  $data['achievement_unlocks']['rank']           = [
    'title' => t('Rank'),
    'help' => t('The rank of a given user for a given achievement. E.g., the rank would be 2 if the user was the second user to ever unlock a given achievement.'),
    'field' => [
      'id' => 'numeric',
      'click sortable' => TRUE,
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
  $data['achievement_unlocks']['seen']           = [
    'title' => t('Seen'),
    'help' => t('Has the user seen the achievement?'),
    'field' => [
      'id' => 'boolean',
      'click sortable' => TRUE,
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'boolean',
    ],
    'argument' => [
      'id' => 'numeric',
    ],
  ];

  return $data;
}
