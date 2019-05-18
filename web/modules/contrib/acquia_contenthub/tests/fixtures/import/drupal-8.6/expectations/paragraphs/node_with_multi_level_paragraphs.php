<?php

/**
 * @file
 * Expectation for node with multi-level paragraphs scenario.
 */

use Drupal\Tests\acquia_contenthub\Kernel\Stubs\CdfExpectations;

$data = [
  'uuid' => [
    'en' => [
      ['value' => '81735e3e-46cf-4c7a-b129-6e5e3b27c66b'],
    ],
  ],
  'langcode' => [
    'en' => [
      ['value' => 'en'],
    ],
  ],
  'type' => [
    'en' => [
      ['target_id' => 'e11f301e-e422-4480-9d2c-9c584d9644af'],
    ],
  ],
  'revision_timestamp' => [
    'en' => [
      ['value' => '1548858600'],
    ],
  ],
  'revision_uid' => [
    'en' => [
      ['target_id' => 'df26061b-02a7-4757-bc51-268a5001ed22'],
    ],
  ],
  'status' => [
    'en' => [
      ['value' => 1],
    ],
  ],
  'title' => [
    'en' => [
      ['value' => 'Page with paragraph'],
    ],
  ],
  'uid' => [
    'en' => [
      ['target_id' => 'df26061b-02a7-4757-bc51-268a5001ed22'],
    ],
  ],
  'created' => [
    'en' => [
      ['value' => '1548699453'],
    ],
  ],
  'changed' => [
    'en' => [
      ['value' => '1548858600'],
    ],
  ],
  'promote' => [
    'en' => [
      ['value' => 1],
    ],
  ],
  'sticky' => [
    'en' => [
      ['value' => 0],
    ],
  ],
  'default_langcode' => [
    'en' => [
      ['value' => 1],
    ],
  ],
  'revision_default' => [
    'en' => [
      ['value' => 1],
    ],
  ],
  'revision_translation_affected' => [
    'en' => [
      ['value' => 1],
    ],
  ],
  'field_paragraphs' => [
    'en' => [
      ['target_id' => '72ad889a-f900-4b2f-ba91-a22fc28e0719'],
    ],
  ],
];

$expectations['81735e3e-46cf-4c7a-b129-6e5e3b27c66b'] = new CdfExpectations($data, [
  'nid',
  'vid',
]);

$data = [
  'uuid' => [
    'en' => [
      ['value' => '72ad889a-f900-4b2f-ba91-a22fc28e0719'],
    ],
  ],
  'revision_id' => [
    'en' => [
      ['value' => '2'],
    ],
  ],
  'langcode' => [
    'en' => [
      ['value' => 'en'],
    ],
  ],
  'type' => [
    'en' => [
      ['target_id' => 'fbd7cbb3-2155-4840-a42c-6dbd6eabb62c'],
    ],
  ],
  'status' => [
    'en' => [
      ['value' => 1],
    ],
  ],
  'created' => [
    'en' => [
      ['value' => '1548858569'],
    ],
  ],
  'changed' => [
    'en' => [
      ['value' => '1548699513'],
    ],
  ],
  'parent_id' => [
    'en' => [
      ['value' => '1'],
    ],
  ],
  'parent_type' => [
    'en' => [
      ['value' => 'node'],
    ],
  ],
  'parent_field_name' => [
    'en' => [
      ['value' => 'field_paragraphs'],
    ],
  ],
  'behavior_settings' => [
    'en' => [
      ['value' => 'a:0:{}'],
    ],
  ],
  'default_langcode' => [
    'en' => [
      ['value' => '1'],
    ],
  ],
  'revision_default' => [
    'en' => [
      ['value' => '1'],
    ],
  ],
  'revision_translation_affected' => [
    'en' => [
      ['value' => '1'],
    ],
  ],
  'field_child_paragraph' => [
    'en' => [
      ['target_id' => 'b7ff50f4-e371-4360-8bc4-7020362de52b'],
    ],
  ],
];

$expectations['72ad889a-f900-4b2f-ba91-a22fc28e0719'] = new CdfExpectations($data, [
  'id',
]);

$data = [
  'uuid' => [
    'en' => [
      ['value' => 'b7ff50f4-e371-4360-8bc4-7020362de52b'],
    ],
  ],
  'revision_id' => [
    'en' => [
      ['value' => '1'],
    ],
  ],
  'langcode' => [
    'en' => [
      ['value' => 'en'],
    ],
  ],
  'type' => [
    'en' => [
      ['target_id' => 'a05533ca-4664-4f56-ab49-d52456e4d8e9'],
    ],
  ],
  'status' => [
    'en' => [
      ['value' => 1],
    ],
  ],
  'created' => [
    'en' => [
      ['value' => '1548858569'],
    ],
  ],
  'changed' => [
    'en' => [
      ['value' => '1548699513'],
    ],
  ],
  'parent_id' => [
    'en' => [
      ['value' => '2'],
    ],
  ],
  'parent_type' => [
    'en' => [
      ['value' => 'paragraph'],
    ],
  ],
  'parent_field_name' => [
    'en' => [
      ['value' => 'field_child_paragraph'],
    ],
  ],
  'behavior_settings' => [
    'en' => [
      ['value' => 'a:0:{}'],
    ],
  ],
  'default_langcode' => [
    'en' => [
      ['value' => '1'],
    ],
  ],
  'revision_default' => [
    'en' => [
      ['value' => '1'],
    ],
  ],
  'revision_translation_affected' => [
    'en' => [
      ['value' => '1'],
    ],
  ],
  'field_p_text' => [
    'en' => [
      ['value' => 'Aliquam sed arcu in lorem vehicula faucibus. Pellentesque elit leo, euismod eget commodo sed, tempor quis tellus. Aliquam quis velit odio.'],
    ],
  ],
];

$expectations['b7ff50f4-e371-4360-8bc4-7020362de52b'] = new CdfExpectations($data, [
  'id',
]);

return $expectations;
