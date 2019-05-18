<?php

/**
 * @file
 * File file expectation.
 */

use Drupal\Tests\acquia_contenthub\Kernel\Stubs\CdfExpectations;

$data = [
  'uuid' => [
    'en' => [
      0 => [
        'value' => 'ff3d6699-52d7-4586-ad24-cca8f1b9459b',
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
        'target_id' => '523f369a-cf63-4e19-9695-069750f8e4c9',
      ],
    ],
  ],
  'filename' => [
    'en' => [
      0 => [
        'value' => '1.txt',
      ],
    ],
  ],
  'uri' => [
    'en' => [
      0 => [
        'value' => 'public://1_1.txt',
      ],
    ],
  ],
  'filemime' => [
    'en' => [
      0 => [
        'value' => 'text/plain',
      ],
    ],
  ],
  'filesize' => [
    'en' => [
      0 => [
        'value' => '880',
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
        'value' => '1546522531',
      ],
    ],
  ],
  'changed' => [
    'en' => [
      0 => [
        'value' => '1546522538',
      ],
    ],
  ],
];

$expectations = ['ff3d6699-52d7-4586-ad24-cca8f1b9459b' => new CdfExpectations($data, ['fid'])];

$data = [
  'uuid' => [
    'en' => [
      0 => [
        'value' => '5021d85b-6784-4185-8b25-d2db32dd5483',
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
        'target_id' => '523f369a-cf63-4e19-9695-069750f8e4c9',
      ],
    ],
  ],
  'filename' => [
    'en' => [
      0 => [
        'value' => '2.txt',
      ],
    ],
  ],
  'uri' => [
    'en' => [
      0 => [
        'value' => 'public://2_1.txt',
      ],
    ],
  ],
  'filemime' => [
    'en' => [
      0 => [
        'value' => 'text/plain',
      ],
    ],
  ],
  'filesize' => [
    'en' => [
      0 => [
        'value' => '880',
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
        'value' => '1546522536',
      ],
    ],
  ],
  'changed' => [
    'en' => [
      0 => [
        'value' => '1546522538',
      ],
    ],
  ],
];
$expectations['5021d85b-6784-4185-8b25-d2db32dd5483'] = new CdfExpectations($data, ['fid']);

return $expectations;
