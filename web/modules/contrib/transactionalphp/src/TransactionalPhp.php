<?php

namespace Drupal\transactionalphp;

use \Gielfeldt\TransactionalPHP\Connection;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class TransactionalPhp.
 *
 * @package Drupal\transactionalphp
 *
 * @author Thomas Gielfeldt <thomas@gielfeldt.dk>
 */
class TransactionalPhp extends Connection implements EventSubscriberInterface {

  use TransactionSubscriberTrait;

  /**
   * TransactionalPhp constructor.
   *
   * @param mixed $connection
   *   The database connection to use.
   */
  public function __construct($connection) {
    parent::__construct();
    $this->trackConnection($connection);
    $this->depth = $connection->transactionDepth();

    // Make sure save points are initialized properly.
    for ($depth = $this->depth; $depth >= 0; $depth--) {
      if (!isset($this->savePoints[$depth])) {
        $this->savePoints[$depth] = isset($this->savePoints[$depth + 1]) ? $this->savePoints[$depth + 1] : $this->idx;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function startTransactionEvent($new_depth) {
    $this->startTransaction($new_depth);
  }

  /**
   * {@inheritdoc}
   */
  public function commitTransactionEvent($new_depth) {
    $this->commitTransaction($new_depth);
  }

  /**
   * {@inheritdoc}
   */
  public function rollbackTransactionEvent($new_depth) {
    $this->rollbackTransaction($new_depth);
  }

  /**
   * {@inheritdoc}
   */
  protected function commitOperations(array $operations) {
    $event = new TransactionalPhpEvent($this, ['operations' => &$operations]);
    $this->container->get('event_dispatcher')->dispatch(TransactionalPhpEvents::PRE_COMMIT, $event);

    parent::commitOperations($operations);
  }

}
