<?php

/**
 * @file
 * Views integration for the rules scheduler module.
 */

/**
 * Implements hook_views_data().
 *
 * Specifies the list of future scheduled tasks displayed on the schedule page.
 */
function rules_scheduler_views_data() {
  $table = [
    'rules_scheduler' => [
      'table' => [
        'group' => t('Rules scheduler'),
        'base' => [
          'field' => 'tid',
          'title' => t('Scheduled Rules components'),
          'help' => t('Scheduled Rules components that are executed based on time and cron'),
          'weight' => -10,
        ],
      ],
      'tid' => [
        'title' => t('Tid'),
        'help' => t('The internal ID of the scheduled component'),
        'field' => [
          'id' => 'standard',
          'click sortable' => TRUE,
        ],
        'filter' => [
          'id' => 'numeric',
        ],
        'sort' => [
          'id' => 'standard',
        ],
      ],
      'config' => [
        'title' => t('Component name'),
        'help' => t('The name of the component'),
        'field' => [
          'id' => 'standard',
          'click sortable' => TRUE,
        ],
        'filter' => [
          'id' => 'component_in_operator',
        ],
        'argument' => [
          'id' => 'string',
        ],
        'sort' => [
          'id' => 'standard',
        ],
      ],
      'date' => [
        'title' => t('Scheduled date'),
        'help' => t('Scheduled date and time stamp'),
        'field' => [
          'id' => 'date',
          'click sortable' => TRUE,
        ],
        'filter' => [
          'id' => 'date',
        ],
        'sort' => [
          'id' => 'standard',
        ],
      ],
      'identifier' => [
        'title' => t('User provided identifier'),
        'help' => t('ID to recognize this specific scheduled task'),
        'field' => [
          'id' => 'standard',
          'click sortable' => TRUE,
        ],
        'filter' => [
          'id' => 'string',
        ],
        'sort' => [
          'id' => 'standard',
        ],
      ],
    ],
  ];

  return $table;
}
