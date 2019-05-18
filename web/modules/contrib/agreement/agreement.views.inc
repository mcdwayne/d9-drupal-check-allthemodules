<?php

/**
 * @file
 * Agreement views info.
 */

/**
 * Implements hook_views_data().
 */
function agreement_views_data() {
  $data = [];
  $data['agreement']['table']['group'] = t('User');
  $data['agreement']['table']['provider'] = 'agreement';

  $data['agreement']['table']['join'] = [
    'users_field_data' => [
      'left_field' => 'uid',
      'field' => 'uid',
      'type' => 'left',
    ],
  ];

  $data['agreement']['agreed_date'] = [
    'title' => t('Agreement date'),
    'help' => t('The date the agreement was submitted.'),
    'field' => [
      'id' => 'date',
      'click sortable' => TRUE,
    ],
    'filter' => [
      'id' => 'date',
    ],
    'sort'    => [
      'id' => 'date',
    ],
  ];

  $data['agreement']['agreed'] = [
    'title' => t('Has agreed'),
    'help' => t('Agreement records shows user has agreed or not.'),
    'field' => [
      'id' => 'boolean',
    ],
    'sort' => [
      'id' => 'numeric',
    ],
    'filter' => [
      'id' => 'boolean',
      'label' => t('Has Agreed'),
      'type' => 'yes-no',
    ],
  ];

  $data['agreement']['type'] = [
    'title' => t('Agreement'),
    'help' => t('The agreement type associated with this agreement record.'),
    'field' => [
      'id' => 'agreement_entity',
    ],
    'filter' => [
      'id' => 'in_operator',
      'options callback' => 'agreement_get_agreement_options',
    ],
    'sort' => [
      'id' => 'string',
    ],
    'argument' => [
      'id' => 'string',
    ],
  ];

  return $data;
}
