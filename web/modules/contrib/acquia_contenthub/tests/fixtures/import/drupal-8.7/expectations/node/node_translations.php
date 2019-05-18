<?php

/**
 * @file
 * Expectation for node translations scenario.
 */

use Drupal\Tests\acquia_contenthub\Kernel\Stubs\CdfExpectations;

$data = [
  'uuid' => [
    'en' => [
      0 => [
        'value' => 'b0137bab-a80e-4305-84fe-4d99ffd906c5',
      ],
    ],
    'be' => [
      0 => [
        'value' => 'b0137bab-a80e-4305-84fe-4d99ffd906c5',
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
        'target_id' => 'de670fcd-dd86-421b-ada1-9be76d749ffd',
      ],
    ],
    'be' => [
      0 => [
        'target_id' => 'de670fcd-dd86-421b-ada1-9be76d749ffd',
      ],
    ],
  ],
  'revision_timestamp' => [
    'en' => [
      0 => [
        'value' => '1547719206',
      ],
    ],
    'be' => [
      0 => [
        'value' => '1547719206',
      ],
    ],
  ],
  'revision_uid' => [
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
  'revision_log' => [
    'en' => [
      0 => [
        'value' => 'гэта перакладзены варыянт',
      ],
    ],
    'be' => [
      0 => [
        'value' => 'гэта перакладзены варыянт',
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
  'title' => [
    'en' => [
      0 => [
        'value' => 'Test page node',
      ],
    ],
    'be' => [
      0 => [
        'value' => 'Тэставая старонка',
      ],
    ],
  ],
  'uid' => [
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
  'created' => [
    'en' => [
      0 => [
        'value' => '1547719026',
      ],
    ],
    'be' => [
      0 => [
        'value' => '1547719101',
      ],
    ],
  ],
  'changed' => [
    'en' => [
      0 => [
        'value' => '1547719095',
      ],
    ],
    'be' => [
      0 => [
        'value' => '1547719206',
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
        'value' => 0,
      ],
    ],
    'be' => [
      0 => [
        'value' => 0,
      ],
    ],
  ],
  'promote' => [
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
  'sticky' => [
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
  'revision_translation_affected' => [
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
  'path' => [
    'en' => [
      0 => [
        'langcode' => 'en',
      ],
    ],
    'be' => [
      0 => [
        'langcode' => 'be',
      ],
    ],
  ],
  'body' => [
    'en' => [
      0 => [
        'value' => "<p>A test page node</p>\r\n",
        'summary' => '',
        'format' => 'basic_html',
      ],
    ],
    'be' => [
      0 => [
        'value' => "<p>Гэта тэст</p>\r\n",
        'summary' => '',
        'format' => 'basic_html',
      ],
    ],
  ],
];

$expectations = [
  'b0137bab-a80e-4305-84fe-4d99ffd906c5' => new CdfExpectations($data, [
    'nid',
    'vid',
  ]),
];

return $expectations;
