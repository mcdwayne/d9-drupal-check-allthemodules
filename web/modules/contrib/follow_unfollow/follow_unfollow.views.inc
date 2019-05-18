<?php

/**
 * @file
 * Views integration for the follow statistics.
 */

/**
 * Implements hook_views_data() for follow_unfollow_views_data().
 */
function follow_unfollow_views_data() {
  $data = [];
  $data['follow_unfollow_statistics'] = [];
  $data['follow_unfollow_statistics']['table'] = [];
  $data['follow_unfollow_statistics']['table']['group'] = t('followunfollow');
  $data['follow_unfollow_statistics']['table']['provider'] = 'follow_unfollow';

  $data['follow_unfollow_statistics']['table']['base'] = [
    // Identifier (primary) field in this table for Views.
    'field' => 'fid',
    // Label in the UI.
    'title' => t('follow unfollow'),
    // Longer description in the UI. Required.
    'help' => t('Follow Unfollow table contains follow unfollow content and can be related to follow unfollow statistics.'),
    'weight' => -10,
  ];

  // Fid field.
  $data['follow_unfollow_statistics']['fid'] = [
    'title' => t('follow unfollow fid'),
    'help' => t('follow unfollow fid field.'),
    'field' => ['id' => 'standard'],
    'sort' => ['id' => 'standard'],
    'filter' => ['id' => 'numeric'],
    'argument' => ['id' => 'numeric'],
  ];
  // Nid field.
  $data['follow_unfollow_statistics']['nid'] = [
    'title' => t('follow unfollow nid'),
    'help' => t('follow unfollow nid field.'),
    'relationship' => [
      'base' => 'node_field_data',
      'base field' => 'nid',
      'field' => 'nid',
      'id' => 'standard',
      'label' => t('node'),
    ],
    'field' => ['id' => 'standard'],
    'sort' => ['id' => 'standard'],
    'filter' => ['id' => 'numeric'],
    'argument' => ['id' => 'numeric'],
  ];
  // Tid field.
  $data['follow_unfollow_statistics']['tid'] = [
    'title' => t('follow unfollow tid'),
    'help' => t('follow unfollow tid field.'),
    'relationship' => [
      'base' => 'taxonomy_term_field_data',
      'base field' => 'tid',
      'field' => 'tid',
      'id' => 'standard',
      'label' => t('taxonomy'),
    ],
    'field' => ['id' => 'standard'],
    'sort' => ['id' => 'standard'],
    'filter' => ['id' => 'numeric'],
    'argument' => ['id' => 'numeric'],
  ];
  // Uid field.
  $data['follow_unfollow_statistics']['uid'] = [
    'title' => t('follow unfollow target uid'),
    'help' => t('follow unfollow uid field.'),
    'relationship' => [
      'base' => 'user_field_data',
      'base field' => 'uid',
      'field' => 'uid',
      'id' => 'standard',
      'label' => t('Target user'),
    ],
    'field' => ['id' => 'standard'],
    'sort' => ['id' => 'standard'],
    'filter' => ['id' => 'numeric'],
    'argument' => ['id' => 'numeric'],
  ];
  // Author uid field.
  $data['follow_unfollow_statistics']['author_uid'] = [
    'title' => t('follow unfollow author uid'),
    'help' => t('follow unfollow author_uid field.'),
    'relationship' => [
      'base' => 'user_field_data',
      'base field' => 'uid',
      'field' => 'author_uid',
      'id' => 'standard',
      'label' => t('Author user'),
    ],
    'field' => ['id' => 'standard'],
    'sort' => ['id' => 'standard'],
    'filter' => ['id' => 'numeric'],
    'argument' => ['id' => 'numeric'],
  ];
  // Status field.
  $data['follow_unfollow_statistics']['status'] = [
    'title' => t('follow unfollow status'),
    'help' => t('follow unfollow status field.'),
    'field' => ['id' => 'boolean'],
    'sort' => ['id' => 'standard'],
    'filter' => [
      'id' => 'boolean',
      'label' => t('status'),
      'type' => 'yes-no',
      'use_equal' => TRUE,
    ],
    'argument' => ['id' => 'numeric'],
  ];
  // Created date.
  $data['follow_unfollow_statistics']['created'] = [
    'title' => t('follow unfollow created timestamp'),
    'help' => t('follow unfollow created field.'),
    'field' => ['id' => 'date'],
    'sort' => ['id' => 'date'],
    'filter' => ['id' => 'date'],
  ];
  // Changed date.
  $data['follow_unfollow_statistics']['changed'] = [
    'title' => t('follow unfollow changed timestamp'),
    'help' => t('follow unfollow changed field.'),
    'field' => ['id' => 'date'],
    'sort' => ['id' => 'date'],
    'filter' => ['id' => 'date'],
  ];

  return $data;
}
