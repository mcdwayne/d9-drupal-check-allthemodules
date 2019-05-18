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
        'value' => '0a70f867-cc1f-4eb3-b025-bf6ee9158425',
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
        'target_id' => '74705889-b2a4-432e-a590-bb8ef384c59a',
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
        'value' => 'public://2018-12/1.txt',
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
        'value' => '1545222195',
      ],
    ],
  ],
  'changed' => [
    'en' => [
      0 => [
        'value' => '1545222198',
      ],
    ],
  ],
];

$expectations = ['0a70f867-cc1f-4eb3-b025-bf6ee9158425' => new CdfExpectations($data, ['fid'])];

return $expectations;
