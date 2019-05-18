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
        'value' => '94b4093e-fb02-4d53-8ecc-031f85fd1db2',
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
        'target_id' => '2caf3e01-7759-4161-807c-d57743ef3500',
      ],
    ],
  ],
  'revision_user' => [
    'en' => [],
  ],
  'revision_log' => [],
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
        'value' => 'Test block 2',
      ],
    ],
  ],
  'changed' => [
    'en' => [
      0 => [
        'value' => '1545824689',
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
        'value' => "<p>test block 2</p>\r\n",
        'summary' => '',
        'format' => 'basic_html',
      ],
    ],
  ],
  'field_text_field' => [
    'en' => [
      0 => [
        'value' => 'a test text field value',
      ],
    ],
  ],
];

$expectations = [
  '94b4093e-fb02-4d53-8ecc-031f85fd1db2' => new CdfExpectations($data, [
    'id',
    'revision_id',
    'revision_created',
    'revision_translation_affected',
  ]),
];

return $expectations;
