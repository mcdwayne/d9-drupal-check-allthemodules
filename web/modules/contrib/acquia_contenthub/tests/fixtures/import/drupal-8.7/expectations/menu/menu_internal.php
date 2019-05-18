<?php

/**
 * @file
 * Expectation for menu internal scenario.
 */

use Drupal\Tests\acquia_contenthub\Kernel\Stubs\CdfExpectations;

$data = [
  'uuid' => [
    'en' => [
      0 => [
        'value' => 'd675d768-a283-41f7-b136-a50603e5b76a',
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
        'value' => 'Internal Link',
      ],
    ],
  ],
  'description' => [
    'en' => [
      0 => [
        'value' => 'An internal link non-entity',
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
        'uri' => 'internal:/admin',
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
        'value' => '1',
      ],
    ],
  ],
  'weight' => [
    'en' => [
      0 => [
        'value' => '-48',
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
        'value' => '1546587302',
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

$expectations = ['d675d768-a283-41f7-b136-a50603e5b76a' => new CdfExpectations($data, ['id', 'revision_created', 'revision_default', 'revision_translation_affected'])];

return $expectations;
