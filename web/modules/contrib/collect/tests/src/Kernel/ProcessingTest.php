<?php

namespace Drupal\Tests\collect\Kernel;

use Drupal\collect\Entity\Container;
use Drupal\collect\Entity\Model;
use Drupal\collect_test\Plugin\collect\Processor\ContextCollector;
use Drupal\KernelTests\KernelTestBase;

/**
 * Test post-processing of Collect containers.
 *
 * @group collect
 */
class ProcessingTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'collect',
    'collect_test',
    'field',
    'hal',
    'rest',
    'user',
    'serialization',
    'system',
    'views',
    'collect_common',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['collect']);
    $this->installEntitySchema('collect_container');
  }

  /**
   * Tests a simple processing workflow.
   *
   * @see \Drupal\collect\Plugin\collect\Model\CollectJson
   * @see \Drupal\collect\Plugin\collect\Processor\IdentifyContacts
   * @see \Drupal\collect\Processor\Postprocessor
   */
  public function testProcessing() {
    Model::create([
      'id' => 'person',
      'label' => 'Person',
      'uri_pattern' => 'http://example.com/person',
      'plugin_id' => 'collectjson',
      'processors' => [
        // ProcessingForm sorts these items by weight before saving.
        [
          'plugin_id' => 'spicer',
          'weight' => 0,
          'spice' => 'pepper',
        ],
        [
          'plugin_id' => 'context_collector',
          'weight' => 1,
        ],
      ],
    ])->save();

    // Saving triggers post-processing.
    Container::create([
      'schema_uri' => 'http://example.com/person',
      'type' => 'application/json',
      'data' => 'Hello World!',
    ])->save();

    $context = ContextCollector::getContext(\Drupal::state());

    $this->assertEqual('pepper', $context['spice']);
  }

}
