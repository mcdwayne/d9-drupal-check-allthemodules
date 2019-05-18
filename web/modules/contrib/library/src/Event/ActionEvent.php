<?php

namespace Drupal\library\Event;

use Drupal\library\Entity\LibraryTransaction;
use Symfony\Component\EventDispatcher\Event;

/**
 * Returns available action.
 */
class ActionEvent extends Event {

  private $transaction;

  /**
   * Constructor.
   */
  public function __construct(LibraryTransaction $transaction) {
    $this->transaction = $transaction;
  }

  /**
   * Returns the kernel in which this event was thrown.
   *
   * @return \Drupal\library\Entity\LibraryTransaction
   *   Transaction.
   */
  public function getTransaction() {
    return $this->transaction;
  }

}
