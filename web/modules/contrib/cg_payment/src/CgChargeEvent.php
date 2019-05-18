<?php

namespace Drupal\cg_payment;

use Creditguard\CgCommandRequestChargeToken;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class CgChargeEvent.
 *
 * @package Drupal\cg_payment
 */
class CgChargeEvent extends Event {

  const PRE_CHARGE = 'event.pre_charge';

  /**
   * The request object.
   *
   * @var \Creditguard\CgCommandRequestChargeToken
   */
  protected $request;

  /**
   * The transaction object.
   *
   * @var \Drupal\cg_payment\TransactionInterface
   */
  protected $transaction;

  /**
   * CgChargeEvent constructor.
   *
   * @param \Creditguard\CgCommandRequestChargeToken $request
   *   The request object.
   * @param \Drupal\cg_payment\TransactionInterface $transaction
   *   The transaction object.
   */
  public function __construct(CgCommandRequestChargeToken $request, TransactionInterface $transaction) {
    $this->request = $request;
    $this->transaction = $transaction;
  }

  /**
   * Get the request.
   *
   * @return \Creditguard\CgCommandRequestChargeToken
   *   The request object.
   */
  public function getRequest() {
    return $this->request;
  }

  /**
   * Get the transaction object.
   *
   * @return \Drupal\cg_payment\TransactionInterface
   *   The transaction object.
   */
  public function getTransaction() {
    return $this->transaction;
  }

}
