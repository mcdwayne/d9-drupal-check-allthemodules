<?php

/**
 * @file
 * Expectation for node translations non default lang node scenario.
 */

use Drupal\Tests\acquia_contenthub\Kernel\Stubs\CdfExpectations;

$data = [
  'uuid' => [
    'be' => [
      0 => [
        'value' => 'c3910d90-e4ff-467e-9bb4-5c1b5bb43008',
      ],
    ],
  ],
  'langcode' => [
    'be' => [
      0 => [
        'value' => 'be',
      ],
    ],
  ],
  'type' => [
    'be' => [
      0 => [
        'target_id' => 'de670fcd-dd86-421b-ada1-9be76d749ffd',
      ],
    ],
  ],
  'revision_timestamp' => [
    'be' => [
      0 => [
        'value' => '1547724947',
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
    'be' => [],
  ],
  'status' => [
    'be' => [
      0 => [
        'value' => '1',
      ],
    ],
  ],
  'title' => [
    'be' => [
      0 => [
        'value' => 'Беларуская старонка',
      ],
    ],
  ],
  'uid' => [
    'be' => [
      0 => [
        'target_id' => '4eda3a4b-4f2a-4c51-bbb8-dc46b5566412',
      ],
    ],
  ],
  'created' => [
    'be' => [
      0 => [
        'value' => '1547724904',
      ],
    ],
  ],
  'changed' => [
    'be' => [
      0 => [
        'value' => '1547724947',
      ],
    ],
  ],
  'content_translation_source' => [
    'be' => [
      0 => [
        'value' => 'und',
      ],
    ],
  ],
  'content_translation_outdated' => [
    'be' => [
      0 => [
        'value' => 0,
      ],
    ],
  ],
  'promote' => [
    'be' => [
      0 => [
        'value' => '0',
      ],
    ],
  ],
  'sticky' => [
    'be' => [
      0 => [
        'value' => '1',
      ],
    ],
  ],
  'default_langcode' => [
    'be' => [
      0 => [
        'value' => '1',
      ],
    ],
  ],
  'revision_default' => [
    'be' => [
      0 => [
        'value' => '1',
      ],
    ],
  ],
  'revision_translation_affected' => [
    'be' => [
      0 => [
        'value' => '1',
      ],
    ],
  ],
  'path' => [
    'be' => [
      0 => [
        'langcode' => 'be',
      ],
    ],
  ],
  'body' => [
    'be' => [
      0 => [
        'value' => "<p>тэставая нода</p>\r\n",
        'summary' => '',
        'format' => 'basic_html',
      ],
    ],
  ],
];

$expectations = [
  'c3910d90-e4ff-467e-9bb4-5c1b5bb43008' => new CdfExpectations($data, [
    'nid',
    'vid',
  ]),
];

return $expectations;
