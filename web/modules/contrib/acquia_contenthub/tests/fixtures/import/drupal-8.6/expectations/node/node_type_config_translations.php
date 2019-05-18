<?php

/**
 * @file
 * Expectation for node type configuration entity translation scenario.
 */

use Drupal\Tests\acquia_contenthub\Kernel\Stubs\CdfExpectations;

$data = [
  'name' => [
    'en' => 'Article',
    'be' => 'Артыкул',
    'ru' => 'Статья',
  ],
  'description' => [
    'en' => 'Use <em>articles</em> for time-sensitive content like news, press releases or blog posts.',
    'be' => 'беларускі пераклад апісання',
    'ru' => 'русский перевод описания',
  ],
  'help' => [
    'en' => '',
    'be' => '',
    'ru' => '',
  ],
];

$expectation = new CdfExpectations($data);
$expectation->setLangcodes(['en', 'be', 'ru']);

return [
  '06bddad6-c004-414f-802a-eade9b2624b6' => $expectation,
];
