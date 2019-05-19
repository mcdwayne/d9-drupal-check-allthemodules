<?php

namespace Drupal\skyword\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the SkywordMedia entity.
 *
 * @ContentEntityType(
 *   id = "skyword_media",
 *   label = @Translation("Skyword Media"),
 *   base_table = "skyword_media",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 * )
 */
class SkywordMedia extends ContentEntityBase implements ContentEntityInterface {

  /** @inheritdoc */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID for the Skyword Media entity.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID for the Skyword Media entity.'))
      ->setReadOnly(TRUE);

    $fields['file_ref'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('File Reference'))
      ->setDescription(t('A reference to the file or media entity item that corresponds to our record.'))
      ->setRequired(TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title for File'))
      ->setDescription(t('The title field to associate with a file'));

    $fields['alt'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Alt text for File'))
      ->setDescription(t('The alt text to associate with a file'));

    return $fields;
  }

}
