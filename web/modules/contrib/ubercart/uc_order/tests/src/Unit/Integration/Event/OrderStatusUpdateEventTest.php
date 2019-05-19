<?php

namespace Drupal\Tests\uc_order\Unit\Integration\Event;

/**
 * Checks that the event "uc_order_status_update" is correctly defined.
 *
 * @coversDefaultClass \Drupal\uc_order\Event\OrderStatusUpdateEvent
 *
 * @group ubercart
 *
 * @requires module rules
 */
class OrderStatusUpdateEventTest extends OrderEventTestBase {

  /**
   * Tests the event metadata.
   */
  public function testOrderStatusUpdateEvent() {
    // Verify our event is discoverable.
    $event = $this->eventManager->createInstance('uc_order_status_update');

    $original_order_context_definition = $event->getContextDefinition('original_order');
    $this->assertSame('entity:uc_order', $original_order_context_definition->getDataType());
    $this->assertSame('Original order', $original_order_context_definition->getLabel());

    $order_context_definition = $event->getContextDefinition('order');
    $this->assertSame('entity:uc_order', $order_context_definition->getDataType());
    $this->assertSame('Updated order', $order_context_definition->getLabel());
  }

}
