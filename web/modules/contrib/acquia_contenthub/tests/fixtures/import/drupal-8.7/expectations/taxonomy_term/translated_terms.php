<?php

/**
 * @file
 * Expectation for translated terms scenario.
 */

use Drupal\Tests\acquia_contenthub\Kernel\Stubs\CdfExpectations;

$expectations = [];
$data = [
  'uuid' => [
    'en' => [
      ['value' => 'ccd971d2-d5fa-41af-b9ce-fdee956f3c08'],
    ],
    'ru' => [
      ['value' => 'ccd971d2-d5fa-41af-b9ce-fdee956f3c08'],
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
  'status' => [
    'en' => [
      ['value' => 1],
    ],
    'ru' => [
      ['value' => 1],
    ],
  ],
  'name' => [
    'en' => [
      ['value' => 'tag#1'],
    ],
    'ru' => [
      ['value' => 'Тэг#1'],
    ],
  ],
  'description' => [
    'en' => [
      ['value' => '<p>tag#1</p>', 'format' => 'html'],

    ],
    'ru' => [
      ['value' => '<p>Тэг#1</p>', 'format' => 'html'],
    ],
  ],
  'changed' => [
    'en' => [
      ['value' => '1548694533'],
    ],
    'ru' => [
      ['value' => '1548694523'],
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
  'content_translation_uid' => [
    'en' => [
      ['target_id' => 'df26061b-02a7-4757-bc51-268a5001ed22'],
    ],
    'ru' => [
      ['target_id' => 'df26061b-02a7-4757-bc51-268a5001ed22'],
    ],
  ],
  'content_translation_created' => [
    'en' => [
      ['value' => '1548693677'],
    ],
    'ru' => [
      ['value' => '1548682668'],
    ],
  ],
];

$expectations['ccd971d2-d5fa-41af-b9ce-fdee956f3c08'] = new CdfExpectations($data, ['tid', 'vid', 'weight', 'revision_id', 'revision_created', 'revision_default', 'revision_log_message', 'revision_translation_affected']);

return $expectations;
