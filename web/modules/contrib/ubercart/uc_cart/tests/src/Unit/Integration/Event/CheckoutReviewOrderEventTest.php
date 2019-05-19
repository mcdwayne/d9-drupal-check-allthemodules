<?php

namespace Drupal\Tests\uc_cart\Unit\Integration\Event;

/**
 * Checks that the event "uc_cart_checkout_review_order" is correctly defined.
 *
 * @coversDefaultClass \Drupal\uc_cart\Event\CheckoutReviewOrderEvent
 *
 * @group ubercart
 *
 * @requires module rules
 */
class CheckoutReviewOrderEventTest extends CartEventTestBase {

  /**
   * Tests the event metadata.
   */
  public function testCheckoutReviewOrderEvent() {
    // Verify our event is discoverable.
    $event = $this->eventManager->createInstance('uc_cart_checkout_review_order');

    $order_context_definition = $event->getContextDefinition('order');
    $this->assertSame('entity:uc_order', $order_context_definition->getDataType());
    $this->assertSame('Order', $order_context_definition->getLabel());
  }

}
