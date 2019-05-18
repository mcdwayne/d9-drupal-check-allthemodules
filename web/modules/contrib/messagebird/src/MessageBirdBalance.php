<?php

namespace Drupal\messagebird;

/**
 * Class MessageBirdBalance.
 *
 * @package Drupal\messagebird
 */
class MessageBirdBalance implements MessageBirdBalanceInterface {

  /**
   * MessageBird Balance object.
   *
   * @var \MessageBird\Objects\Balance
   */
  protected $balance;

  /**
   * MessageBirdServiceBalance constructor.
   *
   * @param MessageBirdClientInterface $client
   *   The full client object.
   */
  public function __construct(MessageBirdClientInterface $client) {
    $client = $client->getClient();
    $this->balance = $client->balance->read();
  }

  /**
   * Get amount of credits.
   *
   * @return float
   *    Total credits.
   */
  public function getAmount() {
    return $this->balance->amount;
  }

  /**
   * Get the balance type.
   *
   * @return string
   *    Type of balance.
   */
  public function getType() {
    return $this->balance->type;
  }

  /**
   * Get payment info.
   *
   * @return string
   *    Payment info.
   */
  public function getPayment() {
    return $this->balance->payment;
  }

}
