<?php

namespace Drupal\entity_counter\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityDescriptionInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\entity_counter\Plugin\EntityCounterSourceInterface;

/**
 * Defines the interface for entity counter.
 */
interface EntityCounterInterface extends ConfigEntityInterface, EntityDescriptionInterface, EntityWithPluginCollectionInterface {

  /**
   * Gets the entity counter initial value.
   *
   * @return string
   *   The entity counter initial value.
   */
  public function getInitialValue();

  /**
   * Sets the entity counter initial value.
   *
   * @param string $initial_value
   *   The entity counter initial value.
   *
   * @return $this
   */
  public function setInitialValue(string $initial_value);

  /**
   * Gets the entity counter maximum value.
   *
   * @return string
   *   The entity counter maximum value.
   */
  public function getMax();

  /**
   * Sets the entity counter maximum value.
   *
   * @param string $max
   *   The entity counter maximum value.
   *
   * @return $this
   */
  public function setMax(string $max);

  /**
   * Gets the entity counter minimum value.
   *
   * @return string
   *   The entity counter minimum value.
   */
  public function getMin();

  /**
   * Sets the entity counter minimum value.
   *
   * @param string $min
   *   The entity counter minimum value.
   *
   * @return $this
   */
  public function setMin(string $min);

  /**
   * Saves a entity counter source for this entity counter.
   *
   * @param \Drupal\entity_counter\Plugin\EntityCounterSourceInterface $source
   *   The entity counter source object.
   *
   * @return string
   *   The entity counter source ID.
   */
  public function addSource(EntityCounterSourceInterface $source);

  /**
   * Deletes a entity counter source from this entity counter.
   *
   * @param \Drupal\entity_counter\Plugin\EntityCounterSourceInterface $source
   *   The entity counter source object.
   *
   * @return $this
   */
  public function deleteSource(EntityCounterSourceInterface $source);

  /**
   * Returns a specific entity counter source.
   *
   * @param string $source_id
   *   The entity counter source ID.
   *
   * @return \Drupal\entity_counter\Plugin\EntityCounterSourceInterface
   *   The entity counter source object.
   */
  public function getSource($source_id);

  /**
   * Update a entity counter source for this entity counter.
   *
   * @param \Drupal\entity_counter\Plugin\EntityCounterSourceInterface $source
   *   The entity counter source object.
   *
   * @return $this
   */
  public function updateSource(EntityCounterSourceInterface $source);

  /**
   * Gets the entity counter sources.
   *
   * @param string $plugin_id
   *   (optional) Plugin id used to return specific plugin instances.
   * @param bool $status
   *   (optional) Status used to return enabled or disabled plugin instances.
   *
   * @return \Drupal\Core\Plugin\DefaultLazyPluginCollection|\Drupal\entity_counter\Plugin\EntityCounterSourceInterface[]
   *   The entity counter source plugin collection.
   */
  public function getSources($plugin_id = NULL, $status = NULL);

  /**
   * Determine if the entity counter has any manual source.
   *
   * @return bool
   *   TRUE if the entity counter has any manual source.
   */
  public function hasManualSources();

  /**
   * Gets the entity counter status.
   *
   * @return string
   *   The entity counter minimum value.
   *   - \Drupal\entity_counter\EntityCounterStatus::OPEN: The entity counter is
   *       open.
   *   - \Drupal\entity_counter\EntityCounterStatus::CLOSED: The entity counter
   *       is closed.
   *   - \Drupal\entity_counter\EntityCounterStatus::MAX_UPPER_LIMIT: The entity
   *     counter value is equal to or less than the maximum value.
   */
  public function getStatus();

  /**
   * Sets the status of the entity counter entity.
   *
   * @param string|bool $status
   *   The status of the entity counter entity.
   *   - TRUE => \Drupal\entity_counter\EntityCounterStatus::OPEN.
   *   - FALSE => \Drupal\entity_counter\EntityCounterStatus::CLOSED.
   *
   * @return $this
   */
  public function setStatus($status);

  /**
   * Returns the entity counter has transactions status indicator.
   *
   * @param string $source_id
   *   The source ID to search transactions, default any source ID.
   *
   * @return bool
   *   TRUE if the entity counter has transactions.
   */
  public function hasTransactions(string $source_id = NULL);

  /**
   * Returns the entity counter closed status indicator.
   *
   * @return bool
   *   TRUE if the entity counter is closed.
   */
  public function isClosed();

  /**
   * Returns the entity counter opened status indicator.
   *
   * @return bool
   *   TRUE if the entity counter is opened.
   */
  public function isOpen();

  /**
   * Gets the entity counter step value.
   *
   * @return string
   *   The entity counter step value.
   */
  public function getStep();

  /**
   * Sets the entity counter step value.
   *
   * @param string $step
   *   The entity counter step value.
   *
   * @return $this
   */
  public function setStep(string $step);

  /**
   * Gets the entity counter value.
   *
   * @param bool $reset
   *   (optional) Whether to reset the entity counter load cache. Defaults to
   *   FALSE.
   *
   * @return string
   *   The entity counter value.
   */
  public function getValue(bool $reset = FALSE);

  /**
   * Updates the entity counter value with the provided transaction value.
   *
   * @param \Drupal\entity_counter\Entity\CounterTransactionInterface $transaction
   *   The entity counter transaction.
   *
   * @return bool
   *   TRUE if the entity counter value has been updated, FALSE if the counter
   *   is closed.
   *
   * @throws \Drupal\entity_counter\Exception\EntityCounterException
   */
  public function updateValue(CounterTransactionInterface $transaction);

}
