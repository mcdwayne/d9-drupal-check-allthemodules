<?php

/**
 * @file
 * Expectation for node with paragraphs scenario.
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
      ['value' => '1548699513'],
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
      ['value' => '1548699513'],
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
      ['target_id' => '54d27dc3-f079-483f-8919-ed579b455271'],
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
      ['value' => '54d27dc3-f079-483f-8919-ed579b455271'],
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
      ['value' => '1548699513'],
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
  'field_p_text' => [
    'en' => [
      ['value' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. '],
    ],
  ],
];

$expectations['54d27dc3-f079-483f-8919-ed579b455271'] = new CdfExpectations($data, ['id']);

return $expectations;
