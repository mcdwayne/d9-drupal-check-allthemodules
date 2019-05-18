<?php

namespace Drupal\commerce_xero;

use Drupal\Core\TypedData\ComplexDataInterface;

/**
 * Provides a common interface for strategies, data and commerce.
 *
 * The implementation is supposed to lower the footprint for storing data in
 * the queue.
 */
interface CommerceXeroDataInterface {

  // @todo Is this the right number?
  public const POISON_THRESHHOLD = 3;

  /**
   * Gets the execution state.
   *
   * @return string
   *   One of "process" or "send".
   */
  public function getExecutionState();

  /**
   * Sets the execution state.
   *
   * @param string $state
   *   One of "process", "send" or "poison".
   */
  public function setExecutionState($state = 'process');

  /**
   * Gets the strategy entity attached to this object.
   *
   * @return string
   *   The commerce_xero_strategy entity ID.
   */
  public function getStrategyEntityId();

  /**
   * Gets the attached complex data type to process.
   *
   * @return \Drupal\Core\TypedData\ComplexDataInterface
   *   Bank Transaction, Payment, Invoice, etc...
   */
  public function getData();

  /**
   * Sets a new data object.
   *
   * @param \Drupal\Core\TypedData\ComplexDataInterface $data
   *   The complex data.
   */
  public function setData(ComplexDataInterface $data);

  /**
   * Gets the commerce payment entity attached to this object.
   *
   * @return int
   *   The commerce_payment entity ID.
   */
  public function getPaymentEntityId();

  /**
   * Increments the count.
   */
  public function incrementCount();

  /**
   * Compares against the number of times the queue item has been processed.
   *
   * @return bool
   *   TRUE if this has exceeded the number of times going trhough the queue.
   */
  public function exceededPoisonThreshhold();

}
