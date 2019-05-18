<?php

namespace Drupal\node_layout_builder\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Defines the LayoutBuilder entity.
 *
 * @ingroup layout_builder
 *
 * @ContentEntityType(
 *   id = "node_layout_builder",
 *   label = @Translation("Node Layout Builder"),
 *   base_table = "node_layout_builder",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 * )
 */
class NodeLayoutBuilder extends ContentEntityBase implements ContentEntityInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Layout entity.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Layout entity.'))
      ->setReadOnly(TRUE);

    $fields['vid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('VID'))
      ->setDescription(t('The revision id.'))
      ->setReadOnly(TRUE);

    $fields['entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Entity Type'))
      ->setDescription(t('The entity type of the item.'))
      ->setReadOnly(TRUE);

    $fields['entity_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Entity ID'))
      ->setDescription(t('The entity id.'))
      ->setReadOnly(TRUE);

    $fields['data'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Data'))
      ->setDescription(t('Data of layout.'))
      ->setReadOnly(TRUE);

    return $fields;
  }

}
