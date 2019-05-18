<?php

/**
 * @file
 * Expectation for taxonomy vocabulary config entity translation scenario.
 */

use Drupal\Tests\acquia_contenthub\Kernel\Stubs\CdfExpectations;

$data = [
  'name' => [
    'en' => 'Tags',
    'be' => 'BE: Tags',
    'ru' => 'RU: Tags',
  ],
  'description' => [
    'en' => 'Use tags to group articles on similar topics into categories.',
    'be' => 'BE: Use tags to group articles on similar topics into categories.',
    'ru' => 'RU: Use tags to group articles on similar topics into categories.',
  ],
];

$expectation = new CdfExpectations($data);
$expectation->setLangcodes(['en', 'be', 'ru']);

return [
  'b6249a32-8c37-4d24-a0f9-c8a4d40a410a' => $expectation,
];
