<?php
namespace Drupal\dibs\Event;

use Drupal\dibs\Entity\DibsTransaction;
use Symfony\Component\EventDispatcher\Event;

class CancelTransactionEvent extends Event {

  /** @var  \Drupal\dibs\Entity\DibsTransaction */
  protected $transaction;

  public function __construct(DibsTransaction $transaction) {
    $this->transaction = $transaction;
  }

  /**
   * @return \Drupal\dibs\Entity\DibsTransaction
   */
  public function getTransaction() {
    return $this->transaction;
  }

}
