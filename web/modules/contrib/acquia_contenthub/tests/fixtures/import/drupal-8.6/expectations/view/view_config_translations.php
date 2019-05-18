<?php

/**
 * @file
 * Expectation for view configuration entity translation scenario.
 */

use Drupal\Core\Entity\EntityInterface;
use Drupal\Tests\acquia_contenthub\Kernel\Stubs\CdfExpectations;

$data = [
  'label' => [
    'en' => 'Content',
    'be' => 'BE: Content',
    'ru' => 'RU: Content',
  ],
  'description' => [
    'en' => 'Find and manage content.',
    'be' => 'BE: Find and manage content.',
    'ru' => 'RU: Find and manage content.',
  ],
];

$expectation = new CdfExpectations($data);
$expectation->setLangcodes(['en', 'be', 'ru']);
$expectation->setEntityLoader(function (): EntityInterface {
  return \Drupal::service('entity.manager')->getStorage('view')->load('content');
});

return [
  '0204f032-73dd-4d0f-83df-019631d86563' => $expectation,
];
