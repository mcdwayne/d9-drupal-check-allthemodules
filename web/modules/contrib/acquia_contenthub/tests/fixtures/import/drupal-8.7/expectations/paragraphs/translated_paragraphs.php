<?php

/**
 * @file
 * Expectation for translated / multi-linguar paragraphs scenario.
 */

use Drupal\Tests\acquia_contenthub\Kernel\Stubs\CdfExpectations;

$data = [
  'uuid' => [
    'en' => [
      ['value' => '50b7a410-35d9-4575-8548-256e958d57de'],
    ],
    'ru' => [
      ['value' => '50b7a410-35d9-4575-8548-256e958d57de'],
    ],
  ],
  'langcode' => [
    'en' => [
      ['value' => 'en'],
    ],
    'ru' => [
      ['value' => 'ru'],
    ],
  ],
  'type' => [
    'en' => [
      ['target_id' => 'e11f301e-e422-4480-9d2c-9c584d9644af'],
    ],
    'ru' => [
      ['target_id' => 'e11f301e-e422-4480-9d2c-9c584d9644af'],
    ],
  ],
  'revision_timestamp' => [
    'en' => [
      ['value' => '1549031594'],
    ],
    'ru' => [
      ['value' => '1549031594'],
    ],
  ],
  'revision_uid' => [
    'en' => [
      ['target_id' => 'df26061b-02a7-4757-bc51-268a5001ed22'],
    ],
    'ru' => [
      ['target_id' => 'df26061b-02a7-4757-bc51-268a5001ed22'],
    ],
  ],
  'status' => [
    'en' => [
      ['value' => 1],
    ],
    'ru' => [
      ['value' => 1],
    ],
  ],
  'title' => [
    'en' => [
      ['value' => 'Test page with paragraph'],
    ],
    'ru' => [
      ['value' => 'Тестовая страница '],
    ],
  ],
  'uid' => [
    'en' => [
      ['target_id' => 'df26061b-02a7-4757-bc51-268a5001ed22'],
    ],
    'ru' => [
      ['target_id' => 'df26061b-02a7-4757-bc51-268a5001ed22'],
    ],
  ],
  'created' => [
    'en' => [
      ['value' => '1549030863'],
    ],
    'ru' => [
      ['value' => '1549030863'],
    ],
  ],
  'changed' => [
    'en' => [
      ['value' => '1549031594'],
    ],
    'ru' => [
      ['value' => '1549031594'],
    ],
  ],
  'promote' => [
    'en' => [
      ['value' => 1],
    ],
    'ru' => [
      ['value' => 1],
    ],
  ],
  'sticky' => [
    'en' => [
      ['value' => 0],
    ],
    'ru' => [
      ['value' => 0],
    ],
  ],
  'default_langcode' => [
    'en' => [
      ['value' => 1],
    ],
    'ru' => [
      ['value' => 0],
    ],
  ],
  'revision_default' => [
    'en' => [
      ['value' => 1],
    ],
    'ru' => [
      ['value' => 1],
    ],
  ],
  'revision_translation_affected' => [
    'en' => [
      ['value' => 1],
    ],
    'ru' => [
      ['value' => 1],
    ],
  ],
  'content_translation_source' => [
    'en' => [
      ['value' => 'und'],
    ],
    'ru' => [
      ['value' => 'en'],
    ],
  ],
  'content_translation_outdated' => [
    'en' => [
      ['value' => 0],
    ],
    'ru' => [
      ['value' => 0],
    ],
  ],
  'field_paragraphs' => [
    'en' => [
      ['target_id' => '26a2f959-b982-41bc-a497-764709dfbeeb'],
    ],
    'ru' => [
      ['target_id' => '26a2f959-b982-41bc-a497-764709dfbeeb'],
    ],
  ],
];

$expectations['50b7a410-35d9-4575-8548-256e958d57de'] = new CdfExpectations($data, [
  'nid',
  'vid',
]);

$data = [
  'uuid' => [
    'en' => [
      ['value' => '26a2f959-b982-41bc-a497-764709dfbeeb'],
    ],
    'ru' => [
      ['value' => '26a2f959-b982-41bc-a497-764709dfbeeb'],
    ],
  ],
  'revision_id' => [
    'en' => [
      ['value' => 2],
    ],
    'ru' => [
      ['value' => 2],
    ],
  ],
  'langcode' => [
    'en' => [
      ['value' => 'en'],
    ],
    'ru' => [
      ['value' => 'ru'],
    ],
  ],
  'type' => [
    'en' => [
      ['target_id' => 'fbd7cbb3-2155-4840-a42c-6dbd6eabb62c'],
    ],
    'ru' => [
      ['target_id' => 'fbd7cbb3-2155-4840-a42c-6dbd6eabb62c'],
    ],
  ],
  'status' => [
    'en' => [
      ['value' => '1'],
    ],
    'ru' => [
      ['value' => '1'],
    ],
  ],
  'created' => [
    'en' => [
      ['value' => '1549030891'],
    ],
    'ru' => [
      ['value' => '1549030891'],
    ],
  ],
  'parent_id' => [
    'en' => [
      ['value' => '1'],
    ],
    'ru' => [
      ['value' => '1'],
    ],
  ],
  'parent_type' => [
    'en' => [
      ['value' => 'node'],
    ],
    'ru' => [
      ['value' => 'node'],
    ],
  ],
  'parent_field_name' => [
    'en' => [
      ['value' => 'field_paragraphs'],
    ],
    'ru' => [
      ['value' => 'field_paragraphs'],
    ],
  ],
  'behavior_settings' => [
    'en' => [
      ['value' => 'a:0:{}'],
    ],
    'ru' => [
      ['value' => 'a:0:{}'],
    ],
  ],
  'default_langcode' => [
    'en' => [
      ['value' => 1],
    ],
    'ru' => [
      ['value' => 0],
    ],
  ],
  'revision_default' => [
    'en' => [
      ['value' => 1],
    ],
    'ru' => [
      ['value' => 1],
    ],
  ],
  'revision_translation_affected' => [
    'en' => [
      ['value' => 1],
    ],
    'ru' => [
      ['value' => 1],
    ],
  ],
  'content_translation_source' => [
    'en' => [
      ['value' => 'und'],
    ],
    'ru' => [
      ['value' => 'en'],
    ],
  ],
  'content_translation_outdated' => [
    'en' => [
      ['value' => 0],
    ],
    'ru' => [
      ['value' => 0],
    ],
  ],
  'field_child_paragraph' => [
    'en' => [
      ['target_id' => 'cd79bce5-4d18-4cc2-a202-3c08cea7701d'],
    ],
    'ru' => [
      ['target_id' => 'cd79bce5-4d18-4cc2-a202-3c08cea7701d'],
    ],
  ],
];

$expectations['26a2f959-b982-41bc-a497-764709dfbeeb'] = new CdfExpectations($data, [
  'id',
  'content_translation_changed',
]);

$data = [
  'uuid' => [
    'en' => [
      ['value' => 'cd79bce5-4d18-4cc2-a202-3c08cea7701d'],
    ],
    'ru' => [
      ['value' => 'cd79bce5-4d18-4cc2-a202-3c08cea7701d'],
    ],
  ],
  'revision_id' => [
    'en' => [
      ['value' => 1],
    ],
    'ru' => [
      ['value' => 1],
    ],
  ],
  'langcode' => [
    'en' => [
      ['value' => 'en'],
    ],
    'ru' => [
      ['value' => 'ru'],
    ],
  ],
  'type' => [
    'en' => [
      ['target_id' => 'a05533ca-4664-4f56-ab49-d52456e4d8e9'],
    ],
    'ru' => [
      ['target_id' => 'a05533ca-4664-4f56-ab49-d52456e4d8e9'],
    ],
  ],
  'status' => [
    'en' => [
      ['value' => 1],
    ],
    'ru' => [
      ['value' => 1],
    ],
  ],
  'created' => [
    'en' => [
      ['value' => '1549030891'],
    ],
    'ru' => [
      ['value' => '1549030891'],
    ],
  ],
  'parent_id' => [
    'en' => [
      ['value' => 2],
    ],
    'ru' => [
      ['value' => 2],
    ],
  ],
  'parent_type' => [
    'en' => [
      ['value' => 'paragraph'],
    ],
    'ru' => [
      ['value' => 'paragraph'],
    ],
  ],
  'parent_field_name' => [
    'en' => [
      ['value' => 'field_child_paragraph'],
    ],
    'ru' => [
      ['value' => 'field_child_paragraph'],
    ],
  ],
  'behavior_settings' => [
    'en' => [
      ['value' => 'a:0:{}'],
    ],
    'ru' => [
      ['value' => 'a:0:{}'],
    ],
  ],
  'default_langcode' => [
    'en' => [
      ['value' => 1],
    ],
    'ru' => [
      ['value' => 0],
    ],
  ],
  'revision_default' => [
    'en' => [
      ['value' => 1],
    ],
    'ru' => [
      ['value' => 1],
    ],
  ],
  'revision_translation_affected' => [
    'en' => [
      ['value' => 1],
    ],
    'ru' => [
      ['value' => 1],
    ],
  ],
  'content_translation_source' => [
    'en' => [
      ['value' => 'und'],
    ],
    'ru' => [
      ['value' => 'en'],
    ],
  ],
  'content_translation_outdated' => [
    'en' => [
      ['value' => 0],
    ],
    'ru' => [
      ['value' => 0],
    ],
  ],
  'field_child_paragraph' => [
    'en' => [
      ['target_id' => 'cd79bce5-4d18-4cc2-a202-3c08cea7701d'],
    ],
    'ru' => [
      ['target_id' => 'cd79bce5-4d18-4cc2-a202-3c08cea7701d'],
    ],
  ],
  'field_p_text' => [
    'en' => [
      ['value' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.'],
    ],
    'ru' => [
      ['value' => 'тест'],
    ],
  ],
];

$expectations['cd79bce5-4d18-4cc2-a202-3c08cea7701d'] = new CdfExpectations($data, [
  'id',
  'content_translation_changed',
]);

return $expectations;
