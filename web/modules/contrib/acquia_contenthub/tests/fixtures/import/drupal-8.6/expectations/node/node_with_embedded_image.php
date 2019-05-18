<?php

/**
 * @file
 * Expectation for node with embedded image scenario.
 */

use Drupal\Tests\acquia_contenthub\Kernel\Stubs\CdfExpectations;

$data = [
  'uuid' => [
    'en' => [
      0 => [
        'value' => 'f88ac4d1-50b9-4d39-b870-e97fa685e248',
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
  'type' => [
    'en' => [
      0 => [
        'target_id' => '33d40dc2-bf36-4877-bc61-42eb7d36cd7c',
      ],
    ],
  ],
  'revision_timestamp' => [
    'en' => [
      0 => [
        'value' => '1547553022',
      ],
    ],
  ],
  'revision_uid' => [
    'en' => [
      0 => [
        'target_id' => 'aed3ece9-159b-4179-a660-46c77b44811d',
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
  'title' => [
    'en' => [
      0 => [
        'value' => 'Article with embedded image',
      ],
    ],
  ],
  'uid' => [
    'en' => [
      0 => [
        'target_id' => 'aed3ece9-159b-4179-a660-46c77b44811d',
      ],
    ],
  ],
  'created' => [
    'en' => [
      0 => [
        'value' => '1547552965',
      ],
    ],
  ],
  'changed' => [
    'en' => [
      0 => [
        'value' => '1547553022',
      ],
    ],
  ],
  'promote' => [
    'en' => [
      0 => [
        'value' => '1',
      ],
    ],
  ],
  'sticky' => [
    'en' => [
      0 => [
        'value' => '0',
      ],
    ],
  ],
  'default_langcode' => [
    'en' => [
      0 => [
        'value' => '1',
      ],
    ],
  ],
  'revision_default' => [
    'en' => [
      0 => [
        'value' => '1',
      ],
    ],
  ],
  'revision_translation_affected' => [
    'en' => [
      0 => [
        'value' => '1',
      ],
    ],
  ],
  'body' => [
    'en' => [
      0 => [
        'value' => '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. In sollicitudin condimentum est, eget bibendum turpis ullamcorper a. Nullam non scelerisque urna. Sed a risus metus. Vivamus fringilla sagittis nisl, a ultrices augue.</p><p><img alt="druplicon" data-entity-type="file" data-entity-uuid="219ebded-70e6-459c-b29b-7686102e9bf3" src="/sites/default/files/inline-images/druplicon_1.png" /></p><p>Interdum et malesuada fames ac ante ipsum primis in faucibus. Mauris sit amet dapibus leo.</p>',
        'summary' => '',
        'format' => 'full_html',
      ],
    ],
  ],
];

$expectations = [];
$expectations['f88ac4d1-50b9-4d39-b870-e97fa685e248'] = new CdfExpectations($data, [
  'nid',
  'vid',
]);

$data = [
  'uuid' => [
    'en' => [
      0 => [
        'value' => '219ebded-70e6-459c-b29b-7686102e9bf3',
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
        'target_id' => 'aed3ece9-159b-4179-a660-46c77b44811d',
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
        'value' => 'public://inline-images/druplicon_1.png',
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
        'value' => '1547553002',
      ],
    ],
  ],
  'changed' => [
    'en' => [
      0 => [
        'value' => '1547553022',
      ],
    ],
  ],
];
$expectations['219ebded-70e6-459c-b29b-7686102e9bf3'] = new CdfExpectations($data, ['fid']);

return $expectations;
