<?php

/**
 * @file
 * Expectation for menu entity scenario.
 */

use Drupal\Tests\acquia_contenthub\Kernel\Stubs\CdfExpectations;

$data = [
  'uuid' => [
    'en' => [
      0 => [
        'value' => 'b335c1e4-1ee7-42b0-9e51-ec24482ca08a',
      ],
    ],
  ],
  'revision_id' => [
    'en' => [
      0 => [
        'value' => 1,
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
  'bundle' => [
    'en' => [
      0 => [
        'value' => 'menu_link_content',
      ],
    ],
  ],
  'enabled' => [
    'en' => [
      0 => [
        'value' => '1',
      ],
    ],
  ],
  'title' => [
    'en' => [
      0 => [
        'value' => 'Entity Link',
      ],
    ],
  ],
  'description' => [
    'en' => [
      0 => [
        'value' => 'A link referencing an entity',
      ],
    ],
  ],
  'menu_name' => [
    'en' => [
      0 => [
        'value' => 'test-menu',
      ],
    ],
  ],
  'link' => [
    'en' => [
      0 => [
        'uri' => '5b11fd8a-31b3-498b-89a2-ffac7456e9df',
        'title' => NULL,
        'options' => [],
      ],
    ],
  ],
  'external' => [
    'en' => [
      0 => [
        'value' => '0',
      ],
    ],
  ],
  'rediscover' => [
    'en' => [
      0 => [
        'value' => '0',
      ],
    ],
  ],
  'weight' => [
    'en' => [
      0 => [
        'value' => '-49',
      ],
    ],
  ],
  'expanded' => [
    'en' => [
      0 => [
        'value' => '0',
      ],
    ],
  ],
  'changed' => [
    'en' => [
      0 => [
        'value' => '1546587328',
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
];

$expectations = ['b335c1e4-1ee7-42b0-9e51-ec24482ca08a' => new CdfExpectations($data, ['id', 'revision_created', 'revision_default', 'revision_translation_affected'])];

return $expectations;
