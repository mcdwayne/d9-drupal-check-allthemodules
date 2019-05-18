<?php

namespace Drupal\Tests\uc_role\Unit\Integration\Event;

/**
 * Checks that the event "uc_role_notify_renew" is correctly defined.
 *
 * @coversDefaultClass \Drupal\uc_role\Event\NotifyRenewEvent
 *
 * @group ubercart
 *
 * @requires module rules
 */
class NotifyRenewEventTest extends RoleEventTestBase {

  /**
   * Tests the event metadata.
   */
  public function testNotifyRenewEvent() {
    // Verify our event is discoverable.
    $event = $this->eventManager->createInstance('uc_role_notify_renew');

    $order_context_definition = $event->getContextDefinition('order');
    $this->assertSame('entity:uc_order', $order_context_definition->getDataType());
    $this->assertSame('Order', $order_context_definition->getLabel());

    $role_context_definition = $event->getContextDefinition('expiration');
    $this->assertSame('array', $role_context_definition->getDataType());
    $this->assertSame('Role expiration', $role_context_definition->getLabel());
  }

}
