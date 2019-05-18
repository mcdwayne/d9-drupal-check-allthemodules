<?php

namespace Drupal\Tests\transactionalphp;

use Drupal\Core\Database\TransactionEvent;
use Drupal\Core\Database\Connection as DatabaseConnection;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Class Mockers.
 *
 * @package Drupal\Tests\transactionalphp
 */
trait Mockers {
  protected $depth = 0;

  /**
   * Get/set current depth.
   *
   * @param int $depth
   *   (optional) The new depth.
   *
   * @return int
   *   The current depth.
   */
  protected function currentDepth($depth = NULL) {
    if (isset($depth)) {
      $this->depth = $depth;
    }
    return $this->depth;
  }

  /**
   * Mock a database connection.
   *
   * @param string $key
   *   The database key.
   * @param string $target
   *   The database target.
   *
   * @return \Drupal\Core\Database\Connection
   *   A mocked database object.
   */
  protected function mockDatabaseConnection($key, $target) {
    $depth = &$this->depth;

    $connection = $this->getMockBuilder('Drupal\Core\Database\Connection')
      ->disableOriginalConstructor()
      ->getMock();
    $connection->method('getKey')
      ->willReturn($key);
    $connection->method('getTarget')
      ->willReturn($target);
    $connection->method('transactionDepth')
      ->will($this->returnCallback(function () use(&$depth) {
        return $depth;
      }));
    return $connection;
  }

  /**
   * Mock a transaction event.
   *
   * @param string $name
   *   The name of the transaction.
   * @param DatabaseConnection $connection
   *   The database connection.
   *
   * @return \Drupal\Core\Database\TransactionEvent
   *   A mocked transaction event object.
   */
  protected function mockTransactionEvent($name, DatabaseConnection $connection) {
    return new TransactionEvent($name, $connection);
  }

  /**
   * Mock a container.
   *
   * @return \Drupal\Core\DependencyInjection\ContainerBuilder
   *   The mocked container.
   */
  protected function mockContainer() {
    $dispatcher = new EventDispatcher();
    $container = new ContainerBuilder();
    $container->set('event_dispatcher', $dispatcher);
    return $container;
  }

}
