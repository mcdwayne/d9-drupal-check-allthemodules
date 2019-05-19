<?php

namespace Drupal\Tests\uc_payment\Unit\Integration\Event;

use Drupal\rules\Core\RulesEventManager;
use Drupal\Tests\rules\Unit\Integration\Event\EventTestBase;

/**
 * Checks that the event "uc_payment_entered" is correctly defined.
 *
 * @coversDefaultClass \Drupal\uc_payment\Event\PaymentEnteredEvent
 *
 * @group ubercart
 *
 * @requires module rules
 */
class PaymentEnteredEventTest extends EventTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Must enable our module to make our plugins discoverable.
    $this->enableModule('uc_payment', [
      'Drupal\\uc_payment' => __DIR__ . '/../../../../../src',
    ]);

    // Tell the plugin manager where to look for plugins.
    $this->moduleHandler->getModuleDirectories()
      ->willReturn(['uc_payment' => __DIR__ . '/../../../../../']);

    // Create a real plugin manager with a mock moduleHandler.
    $this->eventManager = new RulesEventManager($this->moduleHandler->reveal());
  }

  /**
   * Tests the event metadata.
   */
  public function testPaymentEnteredEvent() {
    // Verify our event is discoverable.
    $event = $this->eventManager->createInstance('uc_payment_entered');

    $order_context_definition = $event->getContextDefinition('order');
    $this->assertSame('entity:uc_order', $order_context_definition->getDataType());
    $this->assertSame('Order', $order_context_definition->getLabel());

    $account_context_definition = $event->getContextDefinition('account');
    $this->assertSame('entity:user', $account_context_definition->getDataType());
    $this->assertSame('User', $account_context_definition->getLabel());
  }

}
