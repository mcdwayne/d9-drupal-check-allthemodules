<?php

namespace Drupal\ubercart_funds\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for Transaction entity.
 *
 * @ingroup ubercart_funds
 */
interface TransactionInterface extends ContentEntityInterface, EntityOwnerInterface {

  /**
   * Get the issuer object.
   *
   * @return object
   *   The issuer object.
   */
  public function getIssuer();

  /**
   * Get the issuer ID.
   *
   * @return int
   *   The issuer uid.
   */
  public function getIssuerId();

  /**
   * Set the issuer ID.
   *
   * @param int $uid
   *   The user uid.
   *
   * @return $this
   */
  public function setIssuerId($uid);

  /**
   * Get the recipient object.
   *
   * @return object
   *   The recipient object.
   */
  public function getRecipient();

  /**
   * Get the recipient ID.
   *
   * @return int
   *   The recipient uid.
   */
  public function getRecipientId();

  /**
   * Set the recipient ID.
   *
   * @param int $uid
   *   The user uid.
   *
   * @return $this
   */
  public function setRecipientId($uid);

  /**
   * Get the transaction timestamp.
   *
   * @return int
   *   The transaction timestamp.
   */
  public function getCreatedTime();

  /**
   * Set the issuer ID.
   *
   * @param int $timestamp
   *   The timestamp.
   *
   * @return $this
   */
  public function setCreatedTime($timestamp);

  /**
   * Get the transaction brut amount.
   *
   * @return float
   *   The transaction brut amount.
   */
  public function getBrutAmount();

  /**
   * Set the brut amount.
   *
   * @param float $brut_amount
   *   The transaction brut amount.
   *
   * @return $this
   */
  public function setBrutAmount($brut_amount);

  /**
   * Get the transaction net amount.
   *
   * @return float
   *   The transaction net amount.
   */
  public function getNetAmount();

  /**
   * Set the net amount.
   *
   * @param float $net_amount
   *   The transaction net amount.
   *
   * @return $this
   */
  public function setNetAmount($net_amount);

  /**
   * Get the transaction fee.
   *
   * @return float
   *   The transaction fee.
   */
  public function getFee();

  /**
   * Set the fee.
   *
   * @param float $fee
   *   The transaction fee amount.
   *
   * @return $this
   */
  public function setFee($fee);

  /**
   * Get the transaction currency code.
   *
   * @return string
   *   The transaction currency code.
   */
  public function getCurrencyCode();

  /**
   * Set the currency.
   *
   * @param string $currency_code
   *   The transaction currency code.
   *
   * @return $this
   */
  public function setCurrencyCode($currency_code);

  /**
   * Get the transaction status.
   *
   * @return string
   *   The transaction status.
   */
  public function getStatus();

  /**
   * Set the status.
   *
   * @param string $status
   *   The transaction status.
   *
   * @return $this
   */
  public function setStatus($status);

  /**
   * Get the transaction notes.
   *
   * @return string
   *   The transaction notes.
   */
  public function getNotes();

  /**
   * Set the notes.
   *
   * @param string $notes
   *   The transaction notes.
   *
   * @return $this
   */
  public function setNotes($notes);

}
