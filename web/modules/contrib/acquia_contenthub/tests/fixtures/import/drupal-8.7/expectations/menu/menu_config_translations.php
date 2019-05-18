<?php

/**
 * @file
 * Expectation for menu configuration entity translation scenario.
 */

use Drupal\Tests\acquia_contenthub\Kernel\Stubs\CdfExpectations;

$data = [
  'label' => [
    'en' => 'Test menu',
    'be' => 'BE: Test menu',
    'ru' => 'RU: Test menu',
  ],
  'description' => [
    'en' => 'a test menu',
    'be' => 'BE: a test menu',
    'ru' => 'RU: a test menu',
  ],
  'locked' => [
    'en' => FALSE,
    'be' => FALSE,
    'ru' => FALSE,
  ],
];

$expectation = new CdfExpectations($data);
$expectation->setLangcodes(['en', 'be', 'ru']);

return [
  '33e106d4-b365-4bb1-b44f-8beeecb4616f' => $expectation,
];
