<?php
namespace Drupal\dibs\Event;

use Drupal\dibs\Entity\DibsTransaction;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;

class ApproveTransactionEvent extends Event {

  /** @var  \Drupal\dibs\Entity\DibsTransaction */
  protected $transaction;

  /** @var \Symfony\Component\HttpFoundation\Request */
  protected $request;

  public function __construct(DibsTransaction $transaction, Request $request) {
    $this->transaction = $transaction;
  }

  /**
   * @return \Drupal\dibs\Entity\DibsTransaction
   */
  public function getTransaction() {
    return $this->transaction;
  }

  /**
   * @return \Symfony\Component\HttpFoundation\Request
   */
  public function getRequest() {
    return $this->request;
  }

}
