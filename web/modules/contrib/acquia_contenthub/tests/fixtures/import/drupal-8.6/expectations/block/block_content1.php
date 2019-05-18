<?php

/**
 * @file
 * Expectation for block content scenario.
 */

use Drupal\Tests\acquia_contenthub\Kernel\Stubs\CdfExpectations;

$data = [
  'uuid' => [
    'en' => [
      0 => [
        'value' => '6bf9ea86-92ea-498e-bf5f-4c137a767af3',
      ],
    ],
  ],
  'langcode' => [
    'en' => [
      0 => [
        'value' => 'en',
      ],
    ],
  ],
  'type' => [
    'en' => [
      0 => [
        'target_id' => '55e10ad1-e95b-488d-8fc6-57fd4e24b08c',
      ],
    ],
  ],
  'revision_user' => [
    'en' => [],
  ],
  'revision_log' => [
    'en' => [
      0 => [
        'value' => 'revision log message',
      ],
    ],
  ],
  'status' => [
    'en' => [
      0 => [
        'value' => '1',
      ],
    ],
  ],
  'info' => [
    'en' => [
      0 => [
        'value' => 'Test block',
      ],
    ],
  ],
  'changed' => [
    'en' => [
      0 => [
        'value' => '1545815706',
      ],
    ],
  ],
  'reusable' => [
    'en' => [
      0 => [
        'value' => '1',
      ],
    ],
  ],
  'default_langcode' => [
    'en' => [
      0 => [
        'value' => '1',
      ],
    ],
  ],
  'revision_default' => [
    'en' => [
      0 => [
        'value' => '1',
      ],
    ],
  ],
  'body' => [
    'en' => [
      0 => [
        'value' => "<p>body</p>\r\n",
        'summary' => '',
        'format' => 'basic_html',
      ],
    ],
  ],
];

$expectations = [
  '6bf9ea86-92ea-498e-bf5f-4c137a767af3' => new CdfExpectations($data, [
    'id',
    'revision_id',
    'revision_created',
    'revision_translation_affected',
  ]),
];

return $expectations;
