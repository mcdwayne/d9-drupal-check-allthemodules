<?php

namespace Drupal\commerce_refund_log\Entity;

use Drupal\commerce_price\Price;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Defines the interface for commerce_refund_log_entry.
 *
 * @package Drupal\commerce_refund_log\Entity
 */
interface RefundLogEntryInterface extends ContentEntityInterface {

  /**
   * Gets the parent payment.
   *
   * @return \Drupal\commerce_payment\Entity\PaymentInterface
   *   The payment entity.
   */
  public function getPayment();

  /**
   * Gets the parent payment ID.
   *
   * @return int
   *   The payment ID.
   */
  public function getPaymentId();

  /**
   * Gets the refund remote ID.
   *
   * @return string
   *   The refund remote ID.
   */
  public function getRemoteId();

  /**
   * Sets the refund remote ID.
   *
   * @param string $remote_id
   *   The refund remote ID.
   *
   * @return $this
   */
  public function setRemoteId($remote_id);

  /**
   * Gets the refund remote state.
   *
   * @return string
   *   The refund remote state.
   */
  public function getRemoteState();

  /**
   * Sets the refund remote state.
   *
   * @param string $remote_state
   *   The refund remote state.
   *
   * @return $this
   */
  public function setRemoteState($remote_state);

  /**
   * Gets the refund amount.
   *
   * @return \Drupal\commerce_price\Price
   *   The refund amount.
   */
  public function getAmount();

  /**
   * Sets the refund amount.
   *
   * @param \Drupal\commerce_price\Price $amount
   *   The refund amount.
   *
   * @return $this
   */
  public function setAmount(Price $amount);

  /**
   * Gets the refund timestamp.
   *
   * @return int
   *   The refund timestamp.
   */
  public function getRefundTime();

  /**
   * Sets the refund timestamp.
   *
   * @param int $timestamp
   *   The refund timestamp.
   *
   * @return $this
   */
  public function setRefundTime($timestamp);

}
