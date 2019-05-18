<?php

namespace Drupal\migrate_override;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface OverrideManagerServiceInterface.
 */
interface OverrideManagerServiceInterface {

  const FIELD_IGNORED = 0;

  const FIELD_LOCKED = 1;

  const FIELD_OVERRIDEABLE = 2;

  const ENTITY_FIELD_LOCKED = 0;

  const ENTITY_FIELD_OVERRIDDEN = 1;

  /**
   * Returns the Field instance override setting from config.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $bundle
   *   The bundle.
   * @param string $field
   *   The field name.
   *
   * @return int
   *   The setting value for this instance.
   */
  public function fieldInstanceSetting($entity_type, $bundle, $field);

  /**
   * Returns the field setting by entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to get the field setting for.
   * @param \Drupal\Core\Field\FieldDefinitionInterface|string $field
   *   The field definition or field name.
   *
   * @return int
   *   The field setting.
   */
  public function entityFieldInstanceSetting(ContentEntityInterface $entity, $field);

  /**
   * Determines if we are overriding this bundle.
   *
   * @param string $entity_type_id
   *   The entity type.
   * @param string $bundle
   *   The bundle.
   *
   * @return bool
   *   True if overrides are enabled for this bundle.
   */
  public function bundleEnabled($entity_type_id, $bundle);

  /**
   * Determines if the entity is overrideable.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   *
   * @return bool
   *   True if overrides are enabled for this bundle.
   */
  public function entityBundleEnabled(ContentEntityInterface $entity);

  /**
   * Determines if field exists on bundle.
   *
   * @param string $entity_type_id
   *   The entity.
   * @param string $bundle
   *   The bundle.
   *
   * @return bool
   *   True if field exists.
   */
  public function entityBundleHasField($entity_type_id, $bundle);

  /**
   * Creates the bundle field.
   *
   * @param string $entity_type_id
   *   The entity.
   * @param string $bundle
   *   The bundle.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface
   *   The field.
   */
  public function createBundleField($entity_type_id, $bundle);

  /**
   * Deletes the bundle field.
   *
   * @param string $entity_type_id
   *   The entity.
   * @param string $bundle
   *   The bundle.
   */
  public function deleteBundleField($entity_type_id, $bundle);

  /**
   * Gets an entities status for a field.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   * @param string $field_name
   *   The field name.
   * @param int $default
   *   The default.
   *
   * @return int
   *   The default to return.
   */
  public function getEntityFieldStatus(ContentEntityInterface $entity, $field_name, $default = OverrideManagerServiceInterface::ENTITY_FIELD_LOCKED);

  /**
   * Sets an entities status for a field.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   * @param string $field_name
   *   The field name.
   * @param int $status
   *   The status.
   *
   * @return int
   *   The default to return.
   */
  public function setEntityFieldStatus(ContentEntityInterface $entity, $field_name, $status);

  /**
   * Retrieve option list of overrideable fields.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   *
   * @return array
   *   The options list.
   */
  public function getOverridableEntityFields(ContentEntityInterface $entity);

}
