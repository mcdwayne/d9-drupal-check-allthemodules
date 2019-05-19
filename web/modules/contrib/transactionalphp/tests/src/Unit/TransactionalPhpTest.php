<?php

namespace Drupal\Tests\transactionalphp\Unit;

use Drupal\Tests\transactionalphp\Mockers;
use Drupal\transactionalphp\TransactionalPhp;
use Drupal\Tests\UnitTestCase;
use Drupal\transactionalphp\TransactionalPhpEvent;
use Gielfeldt\TransactionalPHP\Operation;

/**
 * Tests the Transactional PHP library.
 *
 * @group transactionalphp
 *
 * @covers \Drupal\transactionalphp\TransactionalPhp
 * @covers \Drupal\transactionalphp\TransactionSubscriberTrait
 * @covers \Drupal\transactionalphp\TransactionalPhpEvent
 */
class TransactionalPhpTest extends UnitTestCase {

  use Mockers;

  /**
   * Data provider for tests.
   *
   * @return array
   *   Array of arguments for tests.
   */
  public function getDataProvider() {
    $connection = $this->mockDatabaseConnection('default', 'default');
    $container = $this->mockContainer();
    $php = new TransactionalPhp($connection);
    $php->setContainer($container);
    return [$php, $connection];
  }

  /**
   * Test setup.
   */
  public function testSetup() {
    $this->depth = 0;
    list($php, $connection) = $this->getDataProvider();
    $this->assertEquals($connection, $php->getTrackedConnection(), 'Connection not properly set.');
    $this->assertEquals($connection->transactionDepth(), $php->getDepth(), 'Depth not properly set.');

    $this->depth = 2;
    list($php, $connection) = $this->getDataProvider();
    $this->assertEquals($connection, $php->getTrackedConnection(), 'Connection not properly set.');
    $this->assertEquals($connection->transactionDepth(), $php->getDepth(), 'Depth not properly set.');

    $connection = NULL;
    $php->trackConnection($connection);
    $this->assertEquals($connection, $php->getTrackedConnection(), 'Connection not properly set.');
    $this->assertEquals(0, $php->transactionDepth(), 'Depth not properly set.');
  }

  /**
   * Test events.
   */
  public function testEvents() {
    $this->depth = 1;
    list($php, $connection) = $this->getDataProvider();
    $event = $this->mockTransactionEvent('default', $connection);
    $php->startTransactionEventWrapper($event);

    $this->assertEquals(1, $php->getDepth(), 'Depth was not properly set.');

    $performed = FALSE;
    $php->addOperation((new Operation())
      ->onCommit(function () use (&$performed) {
        $performed = TRUE;
      })
    );

    $this->depth = 0;
    $php->commitTransactionEventWrapper($event);
    $this->assertEquals(0, $php->getDepth(), 'Depth was not properly set.');
    $this->assertTrue($performed, 'Operation was not performed.');

    $this->depth = 1;
    $php->startTransactionEventWrapper($event);
    $this->assertEquals(1, $php->getDepth(), 'Depth was not properly set.');

    $this->depth = 0;
    $php->rollbackTransactionEventWrapper($event);
    $this->assertEquals(0, $php->getDepth(), 'Depth was not properly set.');

    $operations = ['test1' => 'value1'];
    $event = new TransactionalPhpEvent($php, ['operations' => &$operations]);
    $operations_by_ref = &$event->getArgument('operations');
    $operations_by_ref['test1'] = 'value2';

    $this->assertEquals($operations, $operations_by_ref, 'Operations not passed by reference through event.');

    try {
      $invalid_argument = &$event->getArgument('nonexistent');
      $exception = FALSE;
    }
    catch (\InvalidArgumentException $e) {
      $exception = TRUE;
    }

    $this->assertTrue($exception, 'Exception not thrown for invalid argument.');
  }

}
