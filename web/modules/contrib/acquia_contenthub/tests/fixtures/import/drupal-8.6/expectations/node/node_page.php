<?php

/**
 * @file
 * Expectation for node page scenario.
 */

use Drupal\Tests\acquia_contenthub\Kernel\Stubs\CdfExpectations;

$data = [
  'uuid' => [
    'en' => [
      0 => [
        'value' => '38f023d8-b0d8-4e8c-9c06-8b547d8a0a85',
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
        'target_id' => 'b7b40bf9-97b4-4c60-873e-0602135a4861',
      ],
    ],
  ],
  'revision_timestamp' => [
    'en' => [
      0 => [
        'value' => '1545339655',
      ],
    ],
  ],
  'revision_uid' => [
    'en' => [
      0 => [
        'target_id' => '8aa0ff11-1f3d-423e-84ac-f3ef22b10f81',
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
        'value' => 'Test English Title',
      ],
    ],
  ],
  'uid' => [
    'en' => [
      0 => [
        'target_id' => '8aa0ff11-1f3d-423e-84ac-f3ef22b10f81',
      ],
    ],
  ],
  'created' => [
    'en' => [
      0 => [
        'value' => '1545339620',
      ],
    ],
  ],
  'changed' => [
    'en' => [
      0 => [
        'value' => '1545339655',
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
    'en' => [
      0 => [
        'value' => 'published',
      ],
    ],
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
        'value' => "This is a test",
        'summary' => '',
        'format' => 'plain_text',
      ],
    ],
  ],
];

$expectations = ['38f023d8-b0d8-4e8c-9c06-8b547d8a0a85' => new CdfExpectations($data, ['nid', 'vid', 'changed'])];

return $expectations;
