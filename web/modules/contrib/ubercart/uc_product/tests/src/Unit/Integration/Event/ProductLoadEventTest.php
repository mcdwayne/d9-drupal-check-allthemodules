<?php

namespace Drupal\Tests\uc_product\Unit\Integration\Event;

use Drupal\rules\Core\RulesEventManager;
use Drupal\Tests\rules\Unit\Integration\Event\EventTestBase;

/**
 * Checks that the event "uc_product_load" is correctly defined.
 *
 * @coversDefaultClass \Drupal\uc_product\Event\ProductLoadEvent
 *
 * @group ubercart
 *
 * @requires module rules
 */
class ProductLoadEventTest extends EventTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Must enable our module to make our plugins discoverable.
    $this->enableModule('uc_product', [
      'Drupal\\uc_product' => __DIR__ . '/../../../../../src',
    ]);

    // Tell the plugin manager where to look for plugins.
    $this->moduleHandler->getModuleDirectories()
      ->willReturn(['uc_product' => __DIR__ . '/../../../../../']);

    // Create a real plugin manager with a mock moduleHandler.
    $this->eventManager = new RulesEventManager($this->moduleHandler->reveal());
  }

  /**
   * Tests the event metadata.
   */
  public function testProductLoadEvent() {
    // Verify our event is discoverable.
    $event = $this->eventManager->createInstance('uc_product_load');

    $product_context_definition = $event->getContextDefinition('product');
    $this->assertSame('entity:node', $product_context_definition->getDataType());
    $this->assertSame('Product', $product_context_definition->getLabel());
  }

}
