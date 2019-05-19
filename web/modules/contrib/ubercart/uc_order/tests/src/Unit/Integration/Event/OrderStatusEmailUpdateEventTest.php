<?php

namespace Drupal\Tests\uc_order\Unit\Integration\Event;

/**
 * Checks that the event "uc_order_status_email_update" is correctly defined.
 *
 * @coversDefaultClass \Drupal\uc_order\Event\OrderStatusEmailUpdateEvent
 *
 * @group ubercart
 *
 * @requires module rules
 */
class OrderStatusEmailUpdateEventTest extends OrderEventTestBase {

  /**
   * Tests the event metadata.
   */
  public function testOrderStatusEmailUpdateEvent() {
    // Verify our event is discoverable.
    $event = $this->eventManager->createInstance('uc_order_status_email_update');

    $order_context_definition = $event->getContextDefinition('order');
    $this->assertSame('entity:uc_order', $order_context_definition->getDataType());
    $this->assertSame('Order', $order_context_definition->getLabel());
  }

}
