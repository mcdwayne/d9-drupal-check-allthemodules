<?php

/**
 * @file
 * Expectation for block translations scenario.
 */

use Drupal\Tests\acquia_contenthub\Kernel\Stubs\CdfExpectations;

$data = [
  'uuid' => [
    'en' => [
      0 => [
        'value' => '0e74a49f-eb49-43ef-9d7a-50c6f500ec87',
      ],
    ],
    'be' => [
      0 => [
        'value' => '0e74a49f-eb49-43ef-9d7a-50c6f500ec87',
      ],
    ],
  ],
  'langcode' => [
    'en' => [
      0 => [
        'value' => 'en',
      ],
    ],
    'be' => [
      0 => [
        'value' => 'be',
      ],
    ],
  ],
  'type' => [
    'en' => [
      0 => [
        'target_id' => 'ac1f5293-a605-42bd-b765-081bc47acf61',
      ],
    ],
    'be' => [
      0 => [
        'target_id' => 'ac1f5293-a605-42bd-b765-081bc47acf61',
      ],
    ],
  ],
  'revision_user' => [
    'en' => [],
  ],
  'revision_log' => [
    'en' => [
      0 => [
        'value' => 'a test block',
      ],
    ],
    'be' => [
      0 => [
        'value' => 'a test block',
      ],
    ],
  ],
  'status' => [
    'en' => [
      0 => [
        'value' => '1',
      ],
    ],
    'be' => [
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
    'be' => [
      0 => [
        'value' => 'Тэставы блок',
      ],
    ],
  ],
  'changed' => [
    'en' => [
      0 => [
        'value' => '1548150561',
      ],
    ],
    'be' => [
      0 => [
        'value' => '1548148357',
      ],
    ],
  ],
  'content_translation_source' => [
    'en' => [
      0 => [
        'value' => 'und',
      ],
    ],
    'be' => [
      0 => [
        'value' => 'en',
      ],
    ],
  ],
  'content_translation_outdated' => [
    'en' => [
      0 => [
        'value' => '0',
      ],
    ],
    'be' => [
      0 => [
        'value' => '0',
      ],
    ],
  ],
  'content_translation_uid' => [
    'en' => [
      0 => [
        'target_id' => '4eda3a4b-4f2a-4c51-bbb8-dc46b5566412',
      ],
    ],
    'be' => [
      0 => [
        'target_id' => '4eda3a4b-4f2a-4c51-bbb8-dc46b5566412',
      ],
    ],
  ],
  'content_translation_created' => [
    'en' => [
      0 => [
        'value' => '1548150561',
      ],
    ],
    'be' => [
      0 => [
        'value' => '1548148357',
      ],
    ],
  ],
  'reusable' => [
    'en' => [
      0 => [
        'value' => '1',
      ],
    ],
    'be' => [
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
    'be' => [
      0 => [
        'value' => '0',
      ],
    ],
  ],
  'revision_default' => [
    'en' => [
      0 => [
        'value' => '1',
      ],
    ],
    'be' => [
      0 => [
        'value' => '1',
      ],
    ],
  ],
  'body' => [
    'en' => [
      0 => [
        'value' => "<p>this is a test block</p>\r\n",
        'summary' => '',
        'format' => 'basic_html',
      ],
    ],
    'be' => [
      0 => [
        'value' => "<p>гэта тэставы блок</p>\r\n",
        'summary' => '',
        'format' => 'basic_html',
      ],
    ],
  ],
];

$expectations = [
  '0e74a49f-eb49-43ef-9d7a-50c6f500ec87' => new CdfExpectations($data, [
    'id',
    'revision_id',
    'revision_created',
    'revision_translation_affected',
  ]),
];

return $expectations;
