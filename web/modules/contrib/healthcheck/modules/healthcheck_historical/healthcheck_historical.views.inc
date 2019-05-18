<?php

/**
 * @file
 * Contains healthcheck\healthcheck.views.inc..
 * Provide a custom views field data that isn't tied to any other module. */

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Render\Markup;
use Drupal\field\FieldConfigInterface;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\system\ActionConfigEntityInterface;

/**
* Implements hook_views_data().
*/
/**
 * Implements hook_views_data().
 */
function healthcheck_historical_views_data() {

  $data = [];

  // Reports table.
  $data['healthcheck_report'] = [];

  $data['healthcheck_report']['table'] = [];
  $data['healthcheck_report']['table']['group'] = t('Healthcheck');
  $data['healthcheck_report']['table']['provider'] = 'healthcheck_historical';

  $data['healthcheck_report']['table']['base'] = [
    'field' => 'id',
    'title' => t('Healthcheck reports'),
    'help' => t('Reports from Healthcheck module'),
  ];

  $data['healthcheck_report']['id'] = [
    'title' => t('Report ID'),
    'help' => t('The unique ID of the report.'),
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

  $data['healthcheck_report']['site'] = [
    'title' => t('Site URL'),
    'help' => t('The domain name of the site for which the report was created.'),
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

  $data['healthcheck_report']['created'] = [
    'title' => t('Created'),
    'help' => t('The UNIX timestamp of when the report was run.'),
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

  $data['healthcheck_report']['finding_count'] = [
    'title' => t('Finding count'),
    'help' => t('Count the number of findings in the report.'),
    'field' => [
      'id' => 'healthcheck_report_finding_count',
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

  // Findings table.
  $data['healthcheck_finding'] = [];

  $data['healthcheck_finding']['table'] = [];
  $data['healthcheck_finding']['table']['group'] = t('Healthcheck');
  $data['healthcheck_finding']['table']['provider'] = 'healthcheck_historical';

  $data['healthcheck_finding']['table']['base'] = [
    'field' => 'id',
    'title' => t('Healthcheck findings'),
    'help' => t('Individual findings (results) from Healthcheck reports'),
  ];

  $data['healthcheck_finding']['report_id'] = [
    'title' => t('Report ID'),
    'help' => t('The report ID to which this finding belongs.'),
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

  $data['healthcheck_finding']['finding_key'] = [
    'title' => t('Finding key'),
    'help' => t('The unique machine name of the finding type.'),
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

  $data['healthcheck_finding']['status'] = [
    'title' => t('Status'),
    'help' => t('The status of the finding.'),
    'field' => [
      'id' => 'healthcheck_finding_status',
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

  $data['healthcheck_finding']['created'] = [
    'title' => t('Created'),
    'help' => t('The UNIX timestamp of when the finding was recorded.'),
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

  $data['healthcheck_finding']['label'] = [
    'title' => t('Label'),
    'help' => t('The label of the finding.'),
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

  $data['healthcheck_finding']['message'] = [
    'title' => t('Message'),
    'help' => t('The message of the finding.'),
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

  $data['views']['table']['group'] = t('Healthcheck');
  $data['views']['table']['join'] = [
    // #global is a special flag which allows a table to appear all the time.
    '#global' => [],
  ];


  $data['views']['finding_status_views_field'] = [
      'title' => t('Finding status'),
      'help' => t('The status of the finding'),
      'group' => t('Healthcheck'),
      'field' => [
          'id' => 'finding_status_views_field',
      ],
  ];

  return $data;
}
