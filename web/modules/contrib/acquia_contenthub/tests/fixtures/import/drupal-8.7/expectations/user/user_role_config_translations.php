<?php

/**
 * @file
 * Expectation for user role configuration entity translation scenario.
 */

use Drupal\Tests\acquia_contenthub\Kernel\Stubs\CdfExpectations;

$data = [
  'label' => [
    'en' => 'Anonymous user',
    'be' => 'BE: Anonymous user',
    'ru' => 'RU: Anonymous user',
  ],
  'weight' => [
    'en' => 0,
    'be' => 0,
    'ru' => 0,
  ],
  'permissions:0' => [
    'en' => 'access comments',
    'be' => 'access comments',
    'ru' => 'access comments',
  ],
];

$expectation = new CdfExpectations($data);
$expectation->setLangcodes(['en', 'be', 'ru']);

return [
  'b7a60b03-3ae2-4480-b261-f72021817346' => $expectation,
];
