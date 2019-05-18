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
        'value' => '0f353016-de0f-4268-859c-9ed58a4d6f36',
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
        'target_id' => '771367cf-7849-414c-833f-5f847cfdfc24',
      ],
    ],
  ],
  'bundle' => [
    'en' => [
      0 => [
        'target_id' => '8d8afe57-3b9e-43d0-aa08-8157a32277a4',
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
  'name' => [
    'en' => [
      0 => [
        'value' => 'Media item#1',
      ],
    ],
  ],
  'thumbnail' => [
    'en' => [
      0 => [
        'target_id' => '083607fb-df43-4efb-a66c-7a44fe018a62',
        'alt' => 'Thumbnail',
        'title' => 'Media item#1',
        'width' => '637',
        'height' => '848',
      ],
    ],
  ],
  'created' => [
    'en' => [
      0 => [
        'value' => '1545832296',
      ],
    ],
  ],
  'changed' => [
    'en' => [
      0 => [
        'value' => '1545832321',
      ],
    ],
  ],
  'default_langcode' => [
    'en' => [
      0 => [
        'value' => 1,
      ],
    ],
  ],
  'revision_default' => [
    'en' => [
      0 => [
        'value' => 1,
      ],
    ],
  ],
  'revision_translation_affected' => [
    'en' => [
      0 => [
        'value' => 1,
      ],
    ],
  ],
  'field_media_image' => [
    'en' => [
      0 => [
        'target_id' => '083607fb-df43-4efb-a66c-7a44fe018a62',
        'alt' => 'Thumbnail',
        'title' => 'Media item#1',
        'width' => '637',
        'height' => '848',
      ],
    ],
  ],
];

// 'revision_created' changes dynamically. Skip this field.
$expectations['0f353016-de0f-4268-859c-9ed58a4d6f36'] = new CdfExpectations($data, [
  'revision_created',
  'mid',
  'vid',
]);

return $expectations;
