<?php

/**
 * @file
 * Expectation for node term page update scenario.
 */

use Drupal\Tests\acquia_contenthub\Kernel\Stubs\CdfExpectations;

$data = [
  'uuid' => [
    'en' => [
      0 => [
        'value' => '1264093e-bdad-41a7-a059-1904a5e6d8d6',
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
        'target_id' => '69f7efaf-cbd7-412e-a717-4f5a1603fe65',
      ],
    ],
  ],
  'revision_timestamp' => [
    'en' => [
      0 => [
        'value' => '1547590795',
      ],
    ],
  ],
  'revision_uid' => [
    'en' => [
      0 => [
        'target_id' => 'fcf9a93d-aa55-4c9e-be8f-a0ff481d9f67',
      ],
    ],
  ],
  'revision_log' => [
    'en' => [],
  ],
  'status' => [
    'en' => [
      0 => [
        'value' => '1',
      ],
    ],
  ],
  'title' => [
    'en' => [
      0 => [
        'value' => 'Test Node',
      ],
    ],
  ],
  'uid' => [
    'en' => [
      0 => [
        'target_id' => 'fcf9a93d-aa55-4c9e-be8f-a0ff481d9f67',
      ],
    ],
  ],
  'created' => [
    'en' => [
      0 => [
        'value' => '1546566612',
      ],
    ],
  ],
  'changed' => [
    'en' => [
      0 => [
        'value' => '1547590795',
      ],
    ],
  ],
  'promote' => [
    'en' => [
      0 => [
        'value' => '0',
      ],
    ],
  ],
  'sticky' => [
    'en' => [
      0 => [
        'value' => '0',
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
  'revision_translation_affected' => [
    'en' => [
      0 => [
        'value' => '1',
      ],
    ],
  ],
  'moderation_state' => [
    'en' => [],
  ],
  'path' => [
    'en' => [
      0 => [
        'langcode' => 'en',
      ],
    ],
  ],
  'body' => [
    'en' => [
      0 => [
        'value' => "<p>Test Node</p>\r\n",
        'summary' => '',
        'format' => 'rich_text',
      ],
    ],
  ],
  'field_custom_category' => [
    'en' => [],
  ],
];

$expectations = ['1264093e-bdad-41a7-a059-1904a5e6d8d6' => new CdfExpectations($data, ['nid', 'vid'])];

return $expectations;
