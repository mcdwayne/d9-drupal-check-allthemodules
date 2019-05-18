<?php

/**
 * @file
 * Expectation for user no email scenario.
 */

use Drupal\Tests\acquia_contenthub\Kernel\Stubs\CdfExpectations;

$data = [
  'uuid' => [
    'en' => [
      0 => [
        'value' => 'f150c156-ef63-4f08-8d69-f15e5ee11106',
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
    'en' => [
      0 => [
        'value' => 'en',
      ],
    ],
  ],
  'name' => [
    'en' => [
      0 => [
        'value' => 'Test user without email',
      ],
    ],
  ],
  'pass' => [
    'en' => [
      0 => [
        'value' => '$S$E1mgL7eX/3AkzGayaS1l7Or5Ggw0DsgUw.ExtaoWQkWaQrJ5JImC',
      ],
    ],
  ],
  'timezone' => [
    'en' => [
      0 => [
        'value' => 'Europe/Budapest',
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
        'value' => '1546435877',
      ],
    ],
  ],
  'changed' => [
    'en' => [
      0 => [
        'value' => '1546520108',
      ],
    ],
  ],
  'access' => [
    'en' => [
      0 => [
        'value' => '1546520744',
      ],
    ],
  ],
  'login' => [
    'en' => [
      0 => [
        'value' => '1546520744',
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
    'en' => [
      0 => [
        'target_id' => '238040d0-e70a-4d39-bed3-b3a74af87678',
      ],
    ],
  ],
];

$expectations = [
  'f150c156-ef63-4f08-8d69-f15e5ee11106' => new CdfExpectations($data, [
    'uid',
    'user_picture',
  ]),
];

return $expectations;
