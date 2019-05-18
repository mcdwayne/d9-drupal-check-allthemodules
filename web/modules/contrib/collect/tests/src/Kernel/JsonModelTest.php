<?php

namespace Drupal\Tests\collect\Kernel;

use Drupal\collect\Entity\Model;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the JSON model plugin.
 *
 * @group collect
 */
class JsonModelTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'collect',
    'collect_common',
    'hal',
    'rest',
    'serialization',
    'views',
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['collect']);
  }

  /**
   * Tests query evaluation.
   */
  public function testEvaluate() {
    $data = [
      'me' => 'Tarzan',
      'you' => 'Jane',
      'date' => [
        'year' => 2015,
        'month' => 4,
        'day' => 15,
      ],
      'fruits' => [
        'apple',
        'banana',
      ],
    ];

    $model = Model::create([
      'id' => 'jungle',
      'label' => 'Jungle JSON',
      'uri_pattern' => 'schema:json',
      'plugin_id' => 'json',
    ]);
    $model->save();

    $json_model_plugin = collect_model_manager()->createInstanceFromConfig($model);

    $query_evaluator = $json_model_plugin->getQueryEvaluator();

    $this->assertEqual('Jane', $query_evaluator->evaluate($data, 'you'));
    // Sub property.
    $this->assertEqual(4, $query_evaluator->evaluate($data, 'date.month'));
    // Numerical key.
    $this->assertEqual('banana', $query_evaluator->evaluate($data, 'fruits.1'));
    // Non-leaf.
    $this->assertEqual(['apple', 'banana'], $query_evaluator->evaluate($data, 'fruits'));
    // Non-existing property.
    $this->assertNull($query_evaluator->evaluate($data, 'carrot'));
  }

}
