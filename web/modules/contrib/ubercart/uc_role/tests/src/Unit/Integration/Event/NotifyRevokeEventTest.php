<?php

namespace Drupal\Tests\uc_role\Unit\Integration\Event;

/**
 * Checks that the event "uc_role_notify_revoke" is correctly defined.
 *
 * @coversDefaultClass \Drupal\uc_role\Event\NotifyRevokeEvent
 *
 * @group ubercart
 *
 * @requires module rules
 */
class NotifyRevokeEventTest extends RoleEventTestBase {

  /**
   * Tests the event metadata.
   */
  public function testNotifyRevokeEvent() {
    // Verify our event is discoverable.
    $event = $this->eventManager->createInstance('uc_role_notify_revoke');

    $account_context_definition = $event->getContextDefinition('account');
    $this->assertSame('entity:user', $account_context_definition->getDataType());
    $this->assertSame('User', $account_context_definition->getLabel());

    $role_context_definition = $event->getContextDefinition('expiration');
    $this->assertSame('array', $role_context_definition->getDataType());
    $this->assertSame('Role expiration', $role_context_definition->getLabel());
  }

}
