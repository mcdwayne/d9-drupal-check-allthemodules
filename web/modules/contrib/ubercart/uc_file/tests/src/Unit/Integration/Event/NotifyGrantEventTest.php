<?php

namespace Drupal\Tests\uc_file\Unit\Integration\Event;

use Drupal\rules\Core\RulesEventManager;
use Drupal\Tests\rules\Unit\Integration\Event\EventTestBase;

/**
 * Checks that the event "uc_file_notify_grant" is correctly defined.
 *
 * @coversDefaultClass \Drupal\uc_file\Event\NotifyGrantEvent
 *
 * @group ubercart
 *
 * @requires module rules
 */
class NotifyGrantEventTest extends EventTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Must enable our module to make our plugins discoverable.
    $this->enableModule('uc_file', [
      'Drupal\\uc_file' => __DIR__ . '/../../../../../src',
    ]);

    // Tell the plugin manager where to look for plugins.
    $this->moduleHandler->getModuleDirectories()
      ->willReturn(['uc_file' => __DIR__ . '/../../../../../']);

    // Create a real plugin manager with a mock moduleHandler.
    $this->eventManager = new RulesEventManager($this->moduleHandler->reveal());
  }

  /**
   * Tests the event metadata.
   */
  public function testNotifyGrantEvent() {
    // Verify our event is discoverable.
    $event = $this->eventManager->createInstance('uc_file_notify_grant');

    $order_context_definition = $event->getContextDefinition('order');
    $this->assertSame('entity:uc_order', $order_context_definition->getDataType());
    $this->assertSame('Order', $order_context_definition->getLabel());

    $file_context_definition = $event->getContextDefinition('expiration');
    $this->assertSame('array', $file_context_definition->getDataType());
    $this->assertSame('File expiration', $file_context_definition->getLabel());
  }

}
