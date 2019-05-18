<?php

namespace Drupal\Tests\audit_log\Unit;

use Drupal\audit_log\AuditLogEvent;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the AuditLogEvent class.
 *
 * @coversDefaultClass \Drupal\audit_log\AuditLogEvent
 * @group audit_log
 */
class AuditLogEventTest extends UnitTestCase {

  /**
   * Verifies that event properties are correctly set and retrieved.
   */
  public function testSettersGetters() {
    /** @var \Drupal\Core\Session\AccountInterface $account */
    $account = $this->getMock(AccountInterface::class);
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $this->getMock(EntityInterface::class);

    $timestamp = time();

    $event = new AuditLogEvent();
    $event->setCurrentState('published')
      ->setEntity($entity)
      ->setEventType('update')
      ->setMessage('Testing the AuditLogEvent Class')
      ->setMessagePlaceholders(['foo' => 'bar'])
      ->setPreviousState('unpublished')
      ->setUser($account)
      ->setRequestTime($timestamp);

    $this->assertEquals('published', $event->getCurrentState());
    $this->assertEquals('update', $event->getEventType());
    $this->assertEquals('Testing the AuditLogEvent Class', $event->getMessage());
    $this->assertEquals(['foo' => 'bar'], $event->getMessagePlaceholders());
    $this->assertEquals('unpublished', $event->getPreviousState());
    $this->assertEquals($account, $event->getUser());
    $this->assertEquals($entity, $event->getEntity());
    $this->assertEquals($timestamp, $event->getRequestTime());
  }

}
