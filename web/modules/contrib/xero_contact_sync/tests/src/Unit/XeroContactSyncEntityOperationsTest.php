<?php

namespace Drupal\Tests\xero_contact_sync\Unit;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Queue\QueueInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\user\UserInterface;
use Drupal\xero_contact_sync\XeroContactSyncEntityOperations;

/**
 * @coversDefaultClass \Drupal\xero_contact_sync\XeroContactSyncEntityOperations
 * @group xero_contact_sync
 */
class XeroContactSyncEntityOperationsTest extends UnitTestCase {

  /**
   * The user creation queue.
   *
   * @var \Drupal\Core\Queue\QueueInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $queue;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $moduleHandler;

  /**
   * @var \Drupal\xero_contact_sync\XeroContactSyncEntityOperations
   */
  protected $entityOperations;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->queue = $this->createMock(QueueInterface::class);
    $this->moduleHandler = $this->createMock(ModuleHandlerInterface::class);
    $this->entityOperations = new XeroContactSyncEntityOperations($this->queue, $this->moduleHandler);
  }

  /**
   * @covers \Drupal\xero_contact_sync\XeroContactSyncEntityOperations::insert
   */
  public function testInsertWithXeroContactId() {
    $this->queue->expects($this->never())
      ->method('createItem');

    $user = $this->createMock(UserInterface::class);
    $user->expects($this->once())
      ->method('get')
      ->with('xero_contact_id')
      ->willReturn((object) ['value' => 'test-contact-id']);

    $this->moduleHandler->expects($this->never())
      ->method('moduleExists')
      ->with('advancedqueue');

    $this->entityOperations->insert($user);
  }

  /**
   * @covers \Drupal\xero_contact_sync\XeroContactSyncEntityOperations::insert
   */
  public function testInsertQueue() {
    $this->queue->expects($this->once())
      ->method('createItem')
      ->with(['user_id' => 12]);
    $this->moduleHandler->expects($this->once())
      ->method('moduleExists')
      ->with('advancedqueue')
      ->willReturn(FALSE);

    $user = $this->createMock(UserInterface::class);
    $user->expects($this->once())
      ->method('get')
      ->with('xero_contact_id')
      ->willReturn((object) ['value' => NULL]);
    $user->expects($this->any())
      ->method('id')
      ->willReturn(12);

    $this->entityOperations->insert($user);
  }

}
