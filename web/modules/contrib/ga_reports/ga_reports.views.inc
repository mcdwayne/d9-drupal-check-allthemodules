<?php

/**
 * @file
 * Views hook implementations for Google Analytics Reports module.
 */

use Drupal\Component\Render\FormattableMarkup;

/**
 * Implements hook_views_data().
 */
function ga_reports_views_data() {
  $data = [
    'ga' => [
      'table' => [
        'group' => t('Google Analytics'),
        'base' => [
          'title' => t('Google Analytics'),
          'query_id' => 'ga_query',
          'help' => t('Views Google Analytics query builder'),
        ],
      ],
      'start_date' => [
        'title' => t('Start date of report'),
        'help' => t('Start date of report'),
        'argument' => [
          'id' => 'ga_argument',
        ],
        'filter' => [
          'id' => 'ga_date',
        ],
        'sort' => [
          'id' => 'date',
        ],
      ],
      'end_date' => [
        'title' => t('End date of report'),
        'help' => t('End date of report'),
        'argument' => [
          'id' => 'ga_argument',
        ],
        'filter' => [
          'id' => 'ga_date',
        ],
        'sort' => [
          'id' => 'date',
        ],
      ],
      'profile_id' => [
        'title' => t('Profile ID'),
        'help' => t('Profile ID'),
        'argument' => [
          'id' => 'ga_argument',
        ],
        'filter' => [
          'id' => 'ga_string',
        ],
      ],
    ],
  ];

  $fields = ga_reports_get_fields();

  foreach ($fields as $field_name => $field) {
    // Description of filed from Google Analytics.
    $field->description = t($field->description) . '<br />' . t('Type: @type.', ['@type' => $field->type]);
    if (isset($field->calculation)) {
      $field->description .= '<br />' . t('Calculation: <code>@formula</code>.',
          ['@formula' => $field->calculation]);
    }
    $field->description .= '<br />' . t('API name: <code>@ga</code>.',
        ['@ga' => 'ga:' . $field_name]);

    // Provide default handler.
    $field_handler = 'standard';
    $float = FALSE;

    if (ga_reports_is_custom($field_name)) {
      $field_handler = 'ga_standard';
    }
    elseif (in_array($field->data_type, ['date', 'time'])) {
      $field_handler = 'date';
    }
    elseif (in_array($field->data_type,
      ['integer', 'float', 'percent', 'currency'])) {

      $field_handler = 'numeric';
      $float = TRUE;
    }
    $data['ga'][$field_name] = [
      'title' => t($field->ui_name),
      // Use this because of escaping markup in Views UI.
      'help' => new FormattableMarkup($field->description, []),
      'group' => t($field->column_group),
      'field' => [
        'id' => $field_handler,
        'click sortable' => TRUE,
        'float' => $float,
      ],
      'sort' => [
        'id' => 'standard',
      ],
      'argument' => [
        'id' => 'ga_argument',
      ],
      'filter' => [
        'id' => ($field->type == 'metric') ? 'ga_numeric' : 'ga_string',
      ],
    ];
  }

  return $data;
}
