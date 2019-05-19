<?php

namespace Drupal\Tests\uc_fulfillment\Unit\Integration\Event;

use Drupal\rules\Core\RulesEventManager;
use Drupal\Tests\rules\Unit\Integration\Event\EventTestBase;

/**
 * Checks that the event "uc_fulfillment_shipment_save" is correctly defined.
 *
 * @coversDefaultClass \Drupal\uc_fulfillment\Event\ShipmentSaveEvent
 *
 * @group ubercart
 *
 * @requires module rules
 */
class ShipmentSaveEventTest extends EventTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Must enable our module to make our plugins discoverable.
    $this->enableModule('uc_fulfillment', [
      'Drupal\\uc_fulfillment' => __DIR__ . '/../../../../../src',
    ]);

    // Tell the plugin manager where to look for plugins.
    $this->moduleHandler->getModuleDirectories()
      ->willReturn(['uc_fulfillment' => __DIR__ . '/../../../../../']);

    // Create a real plugin manager with a mock moduleHandler.
    $this->eventManager = new RulesEventManager($this->moduleHandler->reveal());
  }

  /**
   * Tests the event metadata.
   */
  public function testShipmentSaveEvent() {
    // Verify our event is discoverable.
    $event = $this->eventManager->createInstance('uc_fulfillment_shipment_save');

    $order_context_definition = $event->getContextDefinition('order');
    $this->assertSame('entity:uc_order', $order_context_definition->getDataType());
    $this->assertSame('Order', $order_context_definition->getLabel());

    $shipment_context_definition = $event->getContextDefinition('shipment');
    $this->assertSame('any', $shipment_context_definition->getDataType());
    $this->assertSame('Shipment', $shipment_context_definition->getLabel());
  }

}
