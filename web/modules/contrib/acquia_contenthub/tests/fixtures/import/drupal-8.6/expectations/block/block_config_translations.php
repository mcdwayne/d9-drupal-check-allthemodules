<?php

/**
 * @file
 * Expectation for block configuration entity translation scenario.
 */

use Drupal\Tests\acquia_contenthub\Kernel\Stubs\CdfExpectations;

$data = [
  // The colon ":" character is used to reference a value in a multidimensional
  // configuration array.
  // For the definition given below, actual value would be extracted as follows:
  // $configuration = \Drupal::config($config_name);
  // $actual_value = $configuration->get('settings')['label'].
  'settings:label' => [
    'en' => 'Page title',
    'be' => 'BE: Page title',
    'ru' => 'RU: Page title',
  ],
];

$expectation = new CdfExpectations($data);
$expectation->setLangcodes(['en', 'be', 'ru']);

return [
  '5067cba4-44ba-4e70-ba99-5626343c6b41' => $expectation,
];
