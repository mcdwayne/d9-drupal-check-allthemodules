<?php
/**
 * @file
 * Contains \Drupal\collect\Model\ModelInterface.
 */

namespace Drupal\collect\Model;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;

/**
 * Defines methods for models.
 */
interface ModelInterface extends ConfigEntityInterface, EntityWithPluginCollectionInterface {

  /**
   * Set the label.
   *
   * @param string $label
   *   The new label.
   *
   * @return $this
   */
  public function setLabel($label);

  /**
   * Set the URI match pattern.
   *
   * The pattern determines which containers will use the plugin referenced by
   * this config. See \Drupal\collect\Model\ModelManager::matchUri() for
   * matching rules.
   *
   * @param string $uri_pattern
   *   A URI pattern.
   *
   * @return $this
   */
  public function setUriPattern($uri_pattern);

  /**
   * Returns the URI match pattern.
   *
   * @return string
   *   Configured URI pattern.
   */
  public function getUriPattern();

  /**
   * Set the ID of the plugin to use.
   *
   * @param string $plugin_id
   *   The ID of a model plugin.
   *
   * @return $this
   *
   * @see \Drupal\collect\ModelManagerInterface
   */
  public function setPluginId($plugin_id);

  /**
   * Returns the ID of the associated model plugin.
   *
   * @return string
   *   Model plugin ID.
   */
  public function getPluginId();

  /**
   * Returns whether the model is locked, and thus can not be deleted.
   *
   * The locked flag can be used to protect default config, or programmatically
   * created configs, from deletion by user interaction.
   *
   * @return bool
   *   TRUE if the model is locked, FALSE if it is not.
   */
  public function isLocked();

  /**
   * Enable/disable the container revisions.
   *
   * @param bool $container_revision
   *   TRUE for enabling container revision, FALSE otherwise.
   *
   * @return $this
   */
  public function setContainerRevision($container_revision);

  /**
   * Determines if the container revision is enabled.
   *
   * @return bool
   *   TRUE if the container revision is enabled, FALSE otherwise.
   */
  public function isContainerRevision();

  /**
   * Sets the property definitions.
   *
   * @param array[] $property_definitions
   *   Property definitions as arrays.
   *
   * @return $this
   */
  public function setProperties(array $property_definitions);

  /**
   * Adds or replaces a property definition.
   *
   * @param string $name
   *   Property name.
   * @param string $query
   *   Query used to extract the value.
   * @param array $data_definition
   *   Normalized data definition.
   *
   * @return $this
   */
  public function setProperty($name, $query, array $data_definition);

  /**
   * Removes a property definition.
   *
   * @param string $name
   *   Name of the property to remove.
   *
   * @return $this
   */
  public function unsetProperty($name);

  /**
   * Returns the property definitions.
   *
   * @return array[]
   *   An associative array of property definition arrays, keyed by property
   *   name. Each element has these keys:
   *     - query: Query used to extract the value.
   *     - data_definition: Normalized data definition.
   */
  public function getProperties();

  /**
   * Sets the property definitions.
   *
   * @param \Drupal\Core\TypedData\DataDefinitionInterface[] $property_definitions
   *   Property definitions as data definition objects.
   *
   * @return $this
   */
  public function setTypedProperties(array $property_definitions);

  /**
   * Adds a property definition.
   *
   * @param string $name
   *   Property name.
   * @param \Drupal\collect\Model\PropertyDefinition $property_definition
   *   Property definition object.
   *
   * @return $this
   */
  public function setTypedProperty($name, PropertyDefinition $property_definition);

  /**
   * Returns the property definitions.
   *
   * @return \Drupal\collect\Model\PropertyDefinition[]
   *   An associative array of definition objects, keyed by property name.
   */
  public function getTypedProperties();

  /**
   * Returns a property definition.
   *
   * @param string $name
   *   Property name.
   *
   * @return \Drupal\collect\Model\PropertyDefinition
   *   A definition object.
   */
  public function getTypedProperty($name);

  /**
   * Set the processors that should be applied to containers.
   *
   * @param array $processors
   *   A list of arrays with settings for processors. Each array contains:
   *     - plugin_id: The ID of a processor plugin.
   *
   * @return $this
   */
  public function setProcessors(array $processors);

  /**
   * Returns the processors that should be applied to containers.
   *
   * @return array
   *   A list of arrays with settings for processors. See setProcessors() for
   *   the elements included.
   */
  public function getProcessors();

  /**
   * Removes a processor.
   *
   * @param string $processor_uuid
   *   The UUID of a processor to be removed.
   */
  public function removeProcessor($processor_uuid);

  /**
   * Returns the processor plugin collection.
   *
   * @return \Drupal\collect\Processor\ProcessorPluginCollection
   *   The processors plugin collection.
   */
  public function getProcessorsPluginCollection();

}
