<?php

/**
 * @file
 * Expectation for menu external scenario.
 */

use Drupal\Tests\acquia_contenthub\Kernel\Stubs\CdfExpectations;

$data = [
  'uuid' => [
    'en' => [
      0 => [
        'value' => 'b1dd007c-6720-4497-b54c-879ea2eb6898',
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
        'value' => 'External Link',
      ],
    ],
  ],
  'description' => [
    'en' => [
      0 => [
        'value' => 'An External Link',
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
        'uri' => 'http://google.com',
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
        'value' => '-50',
      ],
    ],
  ],
  'expanded' => [
    'en' => [
      0 => [
        'value' => '1',
      ],
    ],
  ],
  'changed' => [
    'en' => [
      0 => [
        'value' => '1546587343',
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

$expectations = ['b1dd007c-6720-4497-b54c-879ea2eb6898' => new CdfExpectations($data, ['id', 'revision_created', 'revision_default', 'revision_translation_affected'])];

return $expectations;
