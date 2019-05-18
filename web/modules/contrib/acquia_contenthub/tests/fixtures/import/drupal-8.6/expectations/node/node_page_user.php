<?php

/**
 * @file
 * Expectation for node page user scenario.
 */

use Drupal\Tests\acquia_contenthub\Kernel\Stubs\CdfExpectations;

$data = [
  'uuid' => [
    'en' => [
      0 => [
        'value' => '8aa0ff11-1f3d-423e-84ac-f3ef22b10f81',
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
  'preferred_langcode' => [
    'en' => [
      0 => [
        'value' => 'en',
      ],
    ],
  ],
  'preferred_admin_langcode' => [
    'en' => [],
  ],
  'name' => [
    'en' => [
      0 => [
        'value' => 'admin',
      ],
    ],
  ],
  'pass' => [
    'en' => [
      0 => [
        'value' => '$S$EdlZa4zXuYYziGVwZHYWSbhHFuJGrCH5.19OMGkmLi4fOHTy8STw',
      ],
    ],
  ],
  'mail' => [
    'en' => [
      0 => [
        'value' => 'test@test.com',
      ],
    ],
  ],
  'timezone' => [
    'en' => [
      0 => [
        'value' => 'America/Chicago',
      ],
    ],
  ],
  'status' => [
    'en' => [
      0 => [
        'value' => TRUE,
      ],
    ],
  ],
  'created' => [
    'en' => [
      0 => [
        'value' => '1545339522',
      ],
    ],
  ],
  'changed' => [
    'en' => [
      0 => [
        'value' => '1545339558',
      ],
    ],
  ],
  'access' => [
    'en' => [
      0 => [
        'value' => '1545339558',
      ],
    ],
  ],
  'login' => [
    'en' => [
      0 => [
        'value' => '1545339558',
      ],
    ],
  ],
  'init' => [
    'en' => [
      0 => [
        'value' => 'test@test.com',
      ],
    ],
  ],
  'default_langcode' => [
    'en' => [
      0 => [
        'value' => TRUE,
      ],
    ],
  ],
  'roles' => [
    'en' => [],
  ],
];

$expectations = [
  '8aa0ff11-1f3d-423e-84ac-f3ef22b10f81' => new CdfExpectations($data, [
    'uid',
    'user_picture',
  ]),
];

return $expectations;
