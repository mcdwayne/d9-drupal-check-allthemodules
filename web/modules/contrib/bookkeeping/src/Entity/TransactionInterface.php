<?php

namespace Drupal\bookkeeping\Entity;

use Drupal\commerce_price\Price;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides an interface for defining Transaction entities.
 *
 * @ingroup bookkeeping
 */
interface TransactionInterface extends ContentEntityInterface {

  /**
   * Add an entry for the transaction.
   *
   * @param \Drupal\bookkeeping\Entity\AccountInterface|string $account
   *   The account of account ID for the entry.
   * @param \Drupal\commerce_price\Price $amount
   *   The price for the amount/currency code.
   * @param int $type
   *   The type.
   *
   * @return $this
   */
  public function addEntry($account, Price $amount, int $type);

  /**
   * Add a related entity for the transaction.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The related entity.
   *
   * @return $this
   */
  public function addRelated(EntityInterface $entity);

  /**
   * Get the related entities for the transaction.
   *
   * @param string|null $entity_type_id
   *   Optionally filter the entity types to return.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   The related entities.
   */
  public function getRelated(string $entity_type_id = NULL): array;

  /**
   * Get the total value of this transaction.
   *
   * @return \Drupal\commerce_price\Price
   *   The total value of the transaction.
   */
  public function getTotal(): Price;

  /**
   * Gets the Transaction creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Transaction.
   */
  public function getCreatedTime();

  /**
   * Sets the Transaction creation timestamp.
   *
   * @param int $timestamp
   *   The Transaction creation timestamp.
   *
   * @return \Drupal\bookkeeping\Entity\TransactionInterface
   *   The called Transaction entity.
   */
  public function setCreatedTime($timestamp);

}
