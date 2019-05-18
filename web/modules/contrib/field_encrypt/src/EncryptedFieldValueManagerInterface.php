<?php

namespace Drupal\field_encrypt;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface EncryptedFieldValueManagerInterface.
 *
 * @package Drupal\field_encrypt
 */
interface EncryptedFieldValueManagerInterface {

  /**
   * Create an encrypted field value, or update an existing one.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to process.
   * @param string $field_name
   *   The field name to save.
   * @param int $delta
   *   The field delta to save.
   * @param string $property
   *   The field property to save.
   * @param string $encrypted_value
   *   The encrypted value to save.
   *
   * @return \Drupal\field_encrypt\Entity\EncryptedFieldValueInterface
   *   The created EncryptedFieldValue entity.
   */
  public function createEncryptedFieldValue(ContentEntityInterface $entity, $field_name, $delta, $property, $encrypted_value);

  /**
   * Save encrypted field values and link them to their parent entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to save EncryptedFieldValue entities for.
   */
  public function saveEncryptedFieldValues(ContentEntityInterface $entity);

  /**
   * Get an encrypted field value.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to process.
   * @param string $field_name
   *   The field name to retrieve.
   * @param int $delta
   *   The field delta to retrieve.
   * @param string $property
   *   The field property to retrieve.
   *
   * @return string
   *   The encrypted field value.
   */
  public function getEncryptedFieldValue(ContentEntityInterface $entity, $field_name, $delta, $property);

  /**
   * Loads an existing EncryptedFieldValue entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to check.
   * @param string $field_name
   *   The field name to check.
   * @param int $delta
   *   The field delta to check.
   * @param string $property
   *   The field property to check.
   *
   * @return bool|\Drupal\field_encrypt\Entity\EncryptedFieldValue
   *   The existing EncryptedFieldValue entity.
   *
   * @fixme have its own custom method ...
   */
  public function getExistingEntity(ContentEntityInterface $entity, $field_name, $delta, $property);

  /**
   * Delete encrypted field values on a given entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to be deleted.
   */
  public function deleteEntityEncryptedFieldValues(ContentEntityInterface $entity);

  /**
   * Delete encrypted field values on a given entity for a specific field.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity containing the field to be deleted.
   * @param string $field_name
   *   The field name to delete encrypted values for.
   */
  public function deleteEntityEncryptedFieldValuesForField(ContentEntityInterface $entity, $field_name);

  /**
   * Delete encrypted field values for a field on a given entity type.
   *
   * @param string $entity_type
   *   The entity type ID.
   * @param $field_name
   *   The field name to delete EncryptedFieldValue entities for.
   */
  public function deleteEncryptedFieldValuesForField($entity_type, $field_name);
}
