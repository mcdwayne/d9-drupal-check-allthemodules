<?php

namespace Drupal\skyword\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the SkywordPost entity.
 *
 * @ContentEntityType(
 *   id = "skyword_post",
 *   label = @Translation("Skyword Post"),
 *   base_table = "skyword_post",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 * )
 */
class SkywordPost extends ContentEntityBase implements ContentEntityInterface {

  /** @inheritdoc */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID for the Skyword Post entity.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setReadOnly(TRUE)
      ->setDescription(t('The UUID for the Skyword Post entity.'));

    $fields['node_ref'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Node Reference'))
      ->setDescription(t('A reference to the Node item that corresponds to our record.'))
      ->setRequired(TRUE);

    $fields['skywordId'] = BaseFieldDefinition::create('string')
      ->setLabel(t('skywordId for Node'))
      ->setDescription(t('The skywordId field to associate with a node'));

    $fields['trackingTag'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Tracking Tag'))
      ->setDescription(t('The Tracking Tag code to associate with a node'));

    return $fields;
  }

}
