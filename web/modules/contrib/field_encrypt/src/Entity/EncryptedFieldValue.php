<?php

namespace Drupal\field_encrypt\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Defines the EncryptedFieldValue entity.
 *
 * @ingroup field_encrypt
 *
 * @ContentEntityType(
 *   id = "encrypted_field_value",
 *   label = @Translation("Encrypted field value"),
 *   base_table = "encrypted_field",
 *   data_table = "encrypted_field_data",
 *   render_cache = FALSE,
 *   admin_permission = "administer encrypted_field_value entity",
 *   fieldable = FALSE,
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode"
 *   },
 * )
 */
class EncryptedFieldValue extends ContentEntityBase implements EncryptedFieldValueInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Entity type'))
      ->setDescription(t('The entity type for which to store the encrypted value.'))
      ->setSetting('is_ascii', TRUE)
      ->setSetting('max_length', EntityTypeInterface::ID_MAX_LENGTH);

    $fields['entity_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Entity ID'))
      ->setDescription(t('The ID of the entity for which to store the encrypted value.'))
      ->setRequired(TRUE);

    $fields['entity_revision_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Revision ID'))
      ->setDescription(t('The revision ID of the entity.'))
      ->setSetting('unsigned', TRUE);

    $fields['field_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Field name'))
      ->setDescription(t('The field name for which to store the encrypted value.'))
      ->setSetting('is_ascii', TRUE)
      ->setSetting('max_length', FieldStorageConfig::NAME_MAX_LENGTH);

    $fields['field_delta'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Field delta'))
      ->setDescription(t('The field delta.'))
      ->setSetting('unsigned', TRUE);

    $fields['field_property'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Field property'))
      ->setDescription(t('The field property for which to store the encrypted value.'))
      ->setSetting('is_ascii', TRUE)
      ->setSetting('max_length', FieldStorageConfig::NAME_MAX_LENGTH);

    $fields['encrypted_value'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Encrypted value'))
      ->setDescription(t('The encrypted value'))
      ->setTranslatable(TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getEncryptedValue() {
    return $this->get('encrypted_value')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setEncryptedValue($value) {
    $this->set('encrypted_value', $value);
  }

}
