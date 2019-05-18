<?php

namespace Drupal\entity_counter\Plugin;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\entity_counter\Entity\EntityCounterInterface;

/**
 * Defines the interface for entity counter sources.
 *
 * @see \Drupal\entity_counter\Annotation\EntityCounterSource
 * @see \Drupal\entity_counter\Plugin\EntityCounterSourceBase
 * @see \Drupal\entity_counter\Plugin\EntityCounterSourceManagerInterface
 * @see plugin_api
 */
interface EntityCounterSourceInterface extends PluginInspectionInterface, ContainerFactoryPluginInterface, ConfigurablePluginInterface, PluginFormInterface {

  /**
   * Returns the entity counter source cardinality settings.
   *
   * @return string
   *   The entity counter source cardinality settings.
   */
  public function cardinality();

  /**
   * Returns the entity counter source description.
   *
   * @return string
   *   The entity counter source description.
   */
  public function description();

  /**
   * Returns the entity counter source disabled indicator.
   *
   * @return bool
   *   TRUE if the entity counter source is disabled.
   */
  public function isDisabled();

  /**
   * Returns the entity counter source enabled indicator.
   *
   * @return bool
   *   TRUE if the entity counter source is enabled.
   */
  public function isEnabled();

  /**
   * Get the entity counter that this source is attached to.
   *
   * @return \Drupal\entity_counter\Entity\EntityCounterInterface
   *   A entity counter.
   */
  public function getEntityCounter();

  /**
   * Initialize entity counter source.
   *
   * @param \Drupal\entity_counter\Entity\EntityCounterInterface $entity_counter
   *   A entity counter object.
   *
   * @return $this
   */
  public function setEntityCounter(EntityCounterInterface $entity_counter);

  /**
   * Checks if the entity counter source is excluded.
   *
   * @return bool
   *   TRUE if the source is excluded.
   */
  public function isExcluded();

  /**
   * Returns the entity counter source label.
   *
   * @return string
   *   The entity counter source label.
   */
  public function label();

  /**
   * Returns the label of the entity counter source.
   *
   * @return int|string
   *   Either the integer label of the entity counter source, or an empty
   *   string.
   */
  public function getLabel();

  /**
   * Sets the label for this entity counter source.
   *
   * @param int $label
   *   The label for this entity counter source.
   *
   * @return $this
   */
  public function setLabel($label);

  /**
   * Returns the unique ID representing the entity counter source.
   *
   * @return string
   *   The entity counter source ID.
   */
  public function getSourceId();

  /**
   * Acts on entity counter source after it has been created.
   */
  public function createSource();

  /**
   * Acts on entity counter source after it has been updated.
   *
   * @param array $configuration
   *   The new entity counter source configuration.
   * @param array $original_configuration
   *   The old entity counter source configuration.
   */
  public function updateSource(array $configuration, array $original_configuration);

  /**
   * Acts on entity counter source after it has been removed.
   */
  public function deleteSource();

  /**
   * Sets the id for this entity counter source.
   *
   * @param int $source_id
   *   The source_id for this entity counter source.
   *
   * @return $this
   */
  public function setSourceId($source_id);

  /**
   * Returns the status of the entity counter source.
   *
   * @return bool
   *   The status of the entity counter source.
   */
  public function getStatus();

  /**
   * Sets the status for this entity counter source.
   *
   * @param bool $status
   *   The status for this entity counter source.
   *
   * @return $this
   */
  public function setStatus($status);

  /**
   * Returns a render array summarizing the configuration.
   *
   * @return array
   *   A render array.
   */
  public function getSummary();

  /**
   * Adds a transaction.
   *
   * @param float $value
   *   The entity counter transaction entity value.
   * @param \Drupal\Core\Entity\EntityInterface $source_entity
   *   The entity type that produces the transaction.
   * @param string|null $log_message
   *   The transaction log message.
   *
   * @return \Drupal\entity_counter\Entity\CounterTransactionInterface
   *   The created transaction.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function addTransaction(float $value, EntityInterface $source_entity, string $log_message = NULL);

  /**
   * Returns the entity counter source value type.
   *
   * @return int
   *   The entity counter source value type.
   */
  public function valueType();

  /**
   * Returns the weight of the entity counter source.
   *
   * @return int|string
   *   Either the integer weight of the entity counter source, or an empty
   *   string.
   */
  public function getWeight();

  /**
   * Sets the weight for this entity counter source.
   *
   * @param int $weight
   *   The weight for this entity counter source.
   *
   * @return $this
   */
  public function setWeight($weight);

}
