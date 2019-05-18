<?php

namespace Drupal\field_encrypt;

use Drupal\Core\Entity\ContentEntityInterface;


/**
 * Interface for service class to process entities and fields for encryption.
 */
interface FieldEncryptProcessEntitiesInterface {

  /**
   * Check if entity has encrypted fields.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to check.
   *
   * @return bool
   *   TRUE if entity has encrypted fields, FALSE if not.
   */
  public function entityHasEncryptedFields(ContentEntityInterface $entity);

  /**
   * Encrypt fields for an entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to encrypt fields on.
   */
  public function encryptEntity(ContentEntityInterface $entity);

  /**
   * Decrypt fields for an entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to decrypt fields on.
   */
  public function decryptEntity(ContentEntityInterface $entity);

  /**
   * Update the encryption settings on a stored field.
   *
   * @param string $field_name
   *   The field name to update.
   * @param string $field_entity_type
   *   The entity type to update.
   * @param array $original_encryption_settings
   *   Array with original encryption settings to decrypt current values.
   * @param int $entity_id
   *   The entity (revision) ID to update.
   */
  public function updateStoredField($field_name, $field_entity_type, $original_encryption_settings, $entity_id);

  /**
   * Set the cache tags correctly for each encrypted field on an entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity whose fields to set cache tags on.
   * @param $build
   *   The entity render array.
   */
  public function entitySetCacheTags(ContentEntityInterface $entity, &$build);

}
