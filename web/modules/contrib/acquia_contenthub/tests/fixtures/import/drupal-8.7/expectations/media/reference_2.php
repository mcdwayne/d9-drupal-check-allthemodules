<?php

/**
 * @file
 * Media file expectation.
 */

use Drupal\Tests\acquia_contenthub\Kernel\Stubs\CdfExpectations;

$expectations = [];
$data = [
  'uuid' => [
    'en' => [
      0 => [
        'value' => '3cd48a71-8215-4a46-806a-61fdb5cc05d5',
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
  'uid' => [
    'en' => [
      0 => [
        'target_id' => 'bda4cee4-da85-45ed-8a7f-92e8d3e797e7',
      ],
    ],
  ],
  'filename' => [
    'en' => [
      0 => [
        'value' => 'druplicon.png',
      ],
    ],
  ],
  'uri' => [
    'en' => [
      0 => [
        'value' => 'public://druplicon.png',
      ],
    ],
  ],
  'filemime' => [
    'en' => [
      0 => [
        'value' => 'image/png',
      ],
    ],
  ],
  'filesize' => [
    'en' => [
      0 => [
        'value' => '3905',
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
  'created' => [
    'en' => [
      0 => [
        'value' => '1546527525',
      ],
    ],
  ],
  'changed' => [
    'en' => [
      0 => [
        'value' => '1546527535',
      ],
    ],
  ],
];

$expectations['3cd48a71-8215-4a46-806a-61fdb5cc05d5'] = new CdfExpectations($data, ['fid']);

$data = [
  'uuid' => [
    'en' => [
      0 =>
        [
          'value' => 'fcb8efaa-9431-4750-9703-b783b22a4a9f',
        ],
    ],
  ],
  'langcode' => [
    'en' => [
      0 =>
        [
          'value' => 'en',
        ],
    ],
  ],
  'uid' => [
    'en' => [
      0 =>
        [
          'target_id' => 'bda4cee4-da85-45ed-8a7f-92e8d3e797e7',
        ],
    ],
  ],
  'filename' => [
    'en' => [
      0 => [
        'value' => 'druplicon.png',
      ],
    ],
  ],
  'uri' => [
    'en' => [
      0 => [
        'value' => 'public://druplicon_0.png',
      ],
    ],
  ],
  'filemime' => [
    'en' => [
      0 => [
        'value' => 'image/png',
      ],
    ],
  ],
  'filesize' => [
    'en' => [
      0 => [
        'value' => '3905',
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
  'created' => [
    'en' => [
      0 => [
        'value' => '1546527551',
      ],
    ],
  ],
  'changed' => [
    'en' => [
      0 => [
        'value' => '1546527553',
      ],
    ],
  ],
];

$expectations['fcb8efaa-9431-4750-9703-b783b22a4a9f'] = new CdfExpectations($data, ['fid']);

return $expectations;
