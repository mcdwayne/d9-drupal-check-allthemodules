<?php

namespace Drupal\external_entities;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining an external entity type entity.
 */
interface ExternalEntityTypeInterface extends ConfigEntityInterface {

  /**
   * Gets the human-readable name of the entity type.
   *
   * This label should be used to present a human-readable name of the
   * entity type.
   *
   * @return string
   *   The human-readable name of the entity type.
   */
  public function getLabel();

  /**
   * Gets the plural human-readable name of the entity type.
   *
   * This label should be used to present a plural human-readable name of the
   * entity type.
   *
   * @return string
   *   The plural human-readable name of the entity type.
   */
  public function getPluralLabel();

  /**
   * Gets the description.
   *
   * @return string|null
   *   The external entity types description, or NULL if empty.
   */
  public function getDescription();

  /**
   * Returns if entities of this external entity are read only.
   *
   * @return bool
   *   TRUE if the entities are read only, FALSE otherwise.
   */
  public function isReadOnly();

  /**
   * Returns the field mappings.
   *
   * @return array
   *   An associative array of field mappings, keyed by the field name. Each
   *   field mapping is an associative array of property mappings. Also see
   *   ExternalEntityTypeInterface::getFieldMapping().
   */
  public function getFieldMappings();

  /**
   * Returns the field property mapping for a given field.
   *
   * @param string $field_name
   *   The field name.
   * @param string $property_name
   *   (optional) The property name.
   *
   * @return string|array|null
   *   If a valid field and property name is given, the mapped raw data key is
   *   returned. If only a valid field name is given, the mapped raw data keys
   *   are returned as an associative array, keyed by property name. In any
   *   other case, NULL is returned.
   */
  public function getFieldMapping($field_name, $property_name = NULL);

  /**
   * Determines whether the storage client is valid.
   *
   * @return bool
   *   TRUE if the storage client is valid, FALSE otherwise.
   */
  public function hasValidStorageClient();

  /**
   * Retrieves the plugin ID of the storage client for this type.
   *
   * @return string
   *   The plugin ID of the storage client.
   */
  public function getStorageClientId();

  /**
   * Retrieves the storage client.
   *
   * @return \Drupal\external_entities\StorageClient\ExternalEntityStorageClientInterface
   *   This types storage client plugin.
   *
   * @throws \Exception
   *   Thrown if the storage client plugin could not be retrieved.
   */
  public function getStorageClient();

  /**
   * Retrieves the configuration of this types storage client plugin.
   *
   * @return array
   *   An associative array with the storage client configuration.
   */
  public function getStorageClientConfig();

  /**
   * Sets the configuration of this types storage client plugin.
   *
   * @param array $storage_client_config
   *   The new configuration for the storage client.
   *
   * @return $this
   */
  public function setStorageClientConfig(array $storage_client_config);

  /**
   * Gets the maximum age for these entities persistent cache.
   *
   * @return int
   *   The maximum age in seconds. -1 means the entities are cached permanently,
   *   while 0 means entity caching for this external entity type is disabled.
   */
  public function getPersistentCacheMaxAge();

  /**
   * Gets the ID of the associated content entity type.
   *
   * @return string
   *   The entity type ID.
   */
  public function getDerivedEntityTypeId();

  /**
   * Gets the associated content entity type definition.
   *
   * @return \Drupal\Core\Entity\ContentEntityTypeInterface|null
   *   The entity type definition or NULL if it doesn't exist.
   */
  public function getDerivedEntityType();

  /**
   * Returns whether the external entity can be annotated.
   *
   * @return bool
   *   TRUE if the entity can be annotated, FALSE otherwise.
   */
  public function isAnnotatable();

  /**
   * Returns the annotations entity type id.
   *
   * @return string|null
   *   An entity type id or NULL if not annotatable.
   */
  public function getAnnotationEntityTypeId();

  /**
   * Returns the annotations bundle.
   *
   * @return string|null
   *   A bundle or NULL if not annotatable.
   */
  public function getAnnotationBundleId();

  /**
   * Returns the annotations field name.
   *
   * @return string|null
   *   A field name or NULL if not annotatable.
   */
  public function getAnnotationFieldName();

  /**
   * Returns the annotations field.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface|null
   *   A field definition or NULL if not annotatable.
   */
  public function getAnnotationField();

  /**
   * Returns if the external entity inherits its annotation entities fields.
   *
   * @return bool
   *   TRUE if fields are inherited, FALSE otherwise.
   */
  public function inheritsAnnotationFields();

  /**
   * Returns the base path for these external entities.
   *
   * The base path is used to construct the routes these entities live on.
   *
   * @return string
   *   A URL compatible string.
   */
  public function getBasePath();

}
