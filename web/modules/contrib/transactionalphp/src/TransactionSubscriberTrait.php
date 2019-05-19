<?php

namespace Drupal\transactionalphp;

use Drupal\Core\Database\DatabaseEvents;
use Drupal\Core\Database\TransactionEvent;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * TransactionSubscriber trait.
 *
 * @package Drupal\transactionalphp
 *
 * @author Thomas Gielfeldt <thomas@gielfeldt.dk>
 */
trait TransactionSubscriberTrait {

  use ContainerAwareTrait;

  /**
   * The database connection.
   *
   * @var \Drupal\transactionalphp\DatabaseLazyConnection|\Drupal\Core\Database\Connection;
   */
  protected $connection;

  /**
   * Get database connection if available.
   *
   * @return \Drupal\Core\Database\Connection|NULL
   *   The database connection or NULL.
   *
   * @codeCoverageIgnore
   */
  public function getConnection() {
    return $this->connection instanceof DatabaseLazyConnection ? $this->connection->getConnection() : $this->connection;
  }

  /**
   * Get transaction depth (lazy or real).
   *
   * @return int
   *   The transaction depth.
   */
  public function transactionDepth() {
    return isset($this->connection) ? $this->connection->transactionDepth() : 0;
  }

  /**
   * Track a specific connection.
   *
   * @param mixed $connection
   *   A DatabaseConnect or DatabaseLazyConnection instance or NULL.
   */
  public function trackConnection($connection) {
    $this->connection = $connection;
    $this->subscribe();
  }

  /**
   * Get currently tracked connection.
   *
   * @return \Drupal\Core\Database\Connection|\Drupal\transactionalphp\DatabaseLazyConnection
   *   The tracked connection.
   */
  public function getTrackedConnection() {
    return $this->connection;
  }

  /**
   * {@inheritdoc}
   *
   * We exclude this from test coverage, as getSubscribedEvents is tested by
   * Drupal in general.
   *
   * @codeCoverageIgnore
   */
  public function setContainer(ContainerInterface $container = NULL) {
    $this->container = $container;
    $this->subscribe();
  }

  /**
   * Subscribe to events.
   *
   * We exclude this from test coverage, as we don't want to mock the event
   * dispatcher.
   *
   * @codeCoverageIgnore
   */
  protected function subscribe() {
    if (isset($this->container) && isset($this->connection) && $this->container->has('event_dispatcher')) {
      $this->container->get('event_dispatcher')->removeSubscriber($this);
      $this->container->get('event_dispatcher')->addSubscriber($this);
    }
  }

  /**
   * {@inheritdoc}
   *
   * We exclude this from test coverage, as getSubscribedEvents is tested by
   * Drupal in general.
   *
   * @codeCoverageIgnore
   */
  static public function getSubscribedEvents() {
    $events[DatabaseEvents::START_TRANSACTION][] = 'startTransactionEventWrapper';
    $events[DatabaseEvents::COMMIT][] = 'commitTransactionEventWrapper';
    $events[DatabaseEvents::ROLLBACK][] = 'rollbackTransactionEventWrapper';
    return $events;
  }

  /**
   * Start transaction event.
   *
   * Only fire events for the tracked connection.
   *
   * @param \Drupal\Core\Database\TransactionEvent $event
   *   The transaction event.
   */
  public function startTransactionEventWrapper(TransactionEvent $event) {
    if ($this->getConnection() == $event->getDatabaseConnection()) {
      $this->startTransactionEvent($event->getDatabaseConnection()->transactionDepth());
    }
  }

  /**
   * Commit transaction event.
   *
   * Only fire events for the tracked connection.
   *
   * @param \Drupal\Core\Database\TransactionEvent $event
   *   The transaction event.
   */
  public function commitTransactionEventWrapper(TransactionEvent $event) {
    if ($this->getConnection() == $event->getDatabaseConnection()) {
      $this->commitTransactionEvent($event->getDatabaseConnection()->transactionDepth());
    }
  }

  /**
   * Rollback transaction event.
   *
   * Only fire events for the tracked connection.
   *
   * @param \Drupal\Core\Database\TransactionEvent $event
   *   The transaction event.
   */
  public function rollbackTransactionEventWrapper(TransactionEvent $event) {
    if ($this->getConnection() == $event->getDatabaseConnection()) {
      $this->rollbackTransactionEvent($event->getDatabaseConnection()->transactionDepth());
    }
  }

  /**
   * Start a transaction.
   *
   * @param int $new_depth
   *   The depth of the new transaction.
   *
   * @codeCoverageIgnore
   */
  public function startTransactionEvent($new_depth) {
  }

  /**
   * Commit a transaction.
   *
   * @param int $new_depth
   *   The depth of the transaction after commit.
   *
   * @codeCoverageIgnore
   */
  public function commitTransactionEvent($new_depth) {
  }

  /**
   * Rollback a transaction.
   *
   * @param int $new_depth
   *   The depth of the transaction after commit.
   *
   * @codeCoverageIgnore
   */
  public function rollbackTransactionEvent($new_depth) {
  }

}
