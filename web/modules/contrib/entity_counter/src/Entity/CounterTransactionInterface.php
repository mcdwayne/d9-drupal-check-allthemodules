<?php

namespace Drupal\entity_counter\Entity;

use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\RevisionLogInterface;

/**
 * Defines the interface for entity counter transaction.
 */
interface CounterTransactionInterface extends RevisionableInterface, RevisionLogInterface, EntityChangedInterface {

  /**
   * Default source transaction provider.
   */
  const MANUAL_TRANSACTION = 'manual';

  /**
   * Sets the entity counter entity.
   *
   * @param \Drupal\entity_counter\Entity\EntityCounterInterface $entity_counter
   *   The entity counter entity.
   *
   * @return $this
   */
  public function setEntityCounter(EntityCounterInterface $entity_counter);

  /**
   * Gets the entity counter entity.
   *
   * @return \Drupal\entity_counter\Entity\EntityCounterInterface|null
   *   The entity counter entity, or null if unknown.
   */
  public function getEntityCounter();

  /**
   * Gets the entity counter ID.
   *
   * @return int|null
   *   The entity counter ID, or null if unknown.
   */
  public function getEntityCounterId();

  /**
   * Returns the related entity counter source.
   *
   * @return \Drupal\entity_counter\Plugin\EntityCounterSourceInterface
   *   The entity counter source object.
   */
  public function getEntityCounterSource();

  /**
   * Sets the related entity counter source ID.
   *
   * @param string $source_id
   *   The related entity counter source ID.
   *
   * @return $this
   */
  public function setEntityCounterSourceId(string $source_id);

  /**
   * Returns the related entity counter source ID.
   */
  public function getEntityCounterSourceId();

  /**
   * Gets the source entity.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   The source entity.
   */
  public function getSourceEntity();

  /**
   * Gets the entity ID that create this operation.
   *
   * @return int
   *   The entity ID that produces this operation.
   */
  public function getSourceEntityId();

  /**
   * Sets the entity ID that create this transaction.
   *
   * @param int $entity_id
   *   The entity ID that create this transaction.
   *
   * @return $this
   */
  public function setSourceEntityId($entity_id);

  /**
   * Gets the entity type that create this transaction.
   *
   * @return string
   *   The entity type that create this transaction.
   */
  public function getSourceEntityTypeId();

  /**
   * Sets the entity type that produces this transaction.
   *
   * @param string $entity_type
   *   The entity type that create this transaction.
   *
   * @return $this
   */
  public function setSourceEntityTypeId($entity_type);

  /**
   * Cancels an entity transaction.
   *
   * @return $this
   */
  public function cancel();

  /**
   * Returns whether or not the entity counter entity is exceeded limit.
   *
   * @return bool
   *   TRUE if the entity counter entity is recorded, FALSE otherwise.
   */
  public function isExceededLimit();

  /**
   * Sets the entity counter entity as exceeded limit.
   *
   * @return $this
   */
  public function setExceededLimit();

  /**
   * Returns whether or not the entity counter entity is recorded.
   *
   * @return bool
   *   TRUE if the entity counter entity is recorded, FALSE otherwise.
   */
  public function isRecorded();

  /**
   * Sets the entity counter entity as recorded.
   *
   * @return $this
   */
  public function setRecorded();

  /**
   * Returns whether or not the entity counter entity is queued.
   *
   * @return bool
   *   TRUE if the entity counter entity is queued, FALSE otherwise.
   */
  public function isQueued();

  /**
   * Sets the entity counter entity as not queued.
   *
   * @return $this
   */
  public function setQueued();

  /**
   * Returns the status in human readable format.
   *
   * @return string
   *   The human readable format of the current status.
   */
  public function getStatusLabel();

  /**
   * Sets the entity counter transaction operation.
   *
   * @param int|string $operation
   *   The operation value.
   *
   * @return $this
   */
  public function setOperation($operation);

  /**
   * Returns the operation value.
   *
   * @return int
   *   The operation value.
   */
  public function getOperation();

  /**
   * Returns the operation in human readable format.
   *
   * @return string
   *   The human readable format of the current operation.
   */
  public function getOperationLabel();

  /**
   * Applies the transaction value to the entity counter value.
   *
   * @return bool
   *   TRUE if the entity counter transaction has been added to the counter,
   *   FALSE otherwise.
   *
   * @throws \Drupal\entity_counter\Exception\EntityCounterException
   */
  public function applyTransactionValue();

  /**
   * Gets the entity counter transaction entity value.
   *
   * @return int
   *   Value of the entity counter transaction entity.
   */
  public function getTransactionValue();

  /**
   * Sets the entity counter transaction entity value.
   *
   * @param float $value
   *   The entity counter transaction entity value.
   *
   * @return $this
   */
  public function setTransactionValue(float $value);

  /**
   * Default value callback for 'uid' base field definition.
   *
   * @see \Drupal\entity_counter\Entity\CounterTransaction::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getCurrentUserId();

  /**
   * Sets a batch to delete entity counter transactions.
   *
   * @param \Drupal\entity_counter\Entity\CounterTransaction[] $items
   *   An array with the entity counter transactions to delete.
   */
  public static function deleteTransactionsBatch(array $items = []);

  /**
   * Batch finish callback.
   */
  public static function deleteTransactionsBatchFinish($success, $results, $operations);

  /**
   * Batch operation to delete a single entity counter transaction.
   */
  public static function deleteTransactionsBatchOperation($item, &$context);

}
