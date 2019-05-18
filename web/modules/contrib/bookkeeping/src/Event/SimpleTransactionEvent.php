<?php

namespace Drupal\bookkeeping\Event;

use Drupal\commerce_price\Price;

/**
 * Event raised when preparing a simple (one to one) transaction posting.
 */
class SimpleTransactionEvent extends TransactionEvent {

  /**
   * The account to post from (credit).
   *
   * @var string
   */
  protected $from;

  /**
   * The account to post to (debit).
   *
   * @var string
   */
  protected $to;

  /**
   * Construct the Simple Transaction event.
   *
   * @param string $generator
   *   The generator.
   * @param \Drupal\commerce_price\Price $value
   *   The value we are posting.
   * @param string $from
   *   The account to post from (credit).
   * @param string $to
   *   The account to post to (debit).
   */
  public function __construct(string $generator, Price $value, string $from, string $to) {
    parent::__construct($generator, $value);
    $this->from = $from;
    $this->to = $to;
  }

  /**
   * Get the account to post from (credit).
   *
   * @return string
   *   The account ID to post from.
   */
  public function getFrom(): string {
    return $this->from;
  }

  /**
   * Change the account to post from (credit).
   *
   * @param string $income_account
   *   The account ID to post from.
   *
   * @return $this
   */
  public function setFrom(string $income_account) {
    $this->modified = TRUE;
    $this->from = $income_account;
    return $this;
  }

  /**
   * Get the account to post to (debit).
   *
   * @return string
   *   The account ID to post to.
   */
  public function getTo(): string {
    return $this->to;
  }

  /**
   * Change the account to post to (debit).
   *
   * @param string $to
   *   The account ID to post to.
   *
   * @return $this
   */
  public function setTo(string $to) {
    $this->modified = TRUE;
    $this->to = $to;
    return $this;
  }

}
