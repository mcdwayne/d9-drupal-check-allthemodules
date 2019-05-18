<?php

/**
 * @file
 * Expectation for node with links scenario.
 */

use Drupal\Tests\acquia_contenthub\Kernel\Stubs\CdfExpectations;

$data = [
  'uuid' => [
    'en' => [
      0 => [
        'value' => 'fcec27d0-eb50-4ef4-8fb5-2cc736414a7f',
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
        'target_id' => '50579e2a-c79a-487d-b096-3a2202fc9cd1',
      ],
    ],
  ],
  'revision_uid' => [
    'en' => [
      0 => [
        'target_id' => '45244edd-2bd1-47c2-b1e9-12f3cba69136',
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
  'title' => [
    'en' => [
      0 => [
        'value' => 'Article with links',
      ],
    ],
  ],
  'uid' => [
    'en' => [
      0 => [
        'target_id' => '45244edd-2bd1-47c2-b1e9-12f3cba69136',
      ],
    ],
  ],
  'promote' => [
    'en' => [
      0 => [
        'value' => '1',
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
  'field_link' => [
    'en' => [
      0 => [
        'uri' => 'e1319627-4c77-4be9-addd-a0ddfd661392',
        'title' => 'Entity link',
        'options' => [],
      ],
      1 => [
        'uri' => 'internal:/',
        'title' => 'Internal link',
        'options' => [],
      ],
      2 => [
        'uri' => 'http://www.example.com',
        'title' => 'External link',
        'options' => [],
      ],
    ],
  ],
];

$expectations = [];
$expectations['fcec27d0-eb50-4ef4-8fb5-2cc736414a7f'] = new CdfExpectations($data, [
  'nid',
  'vid',
  'path',
  'comment',
  'revision_timestamp',
  'created',
  'changed',
]);

return $expectations;
