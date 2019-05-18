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
 *   id = "node_layout_builder_template",
 *   label = @Translation("Node Layout Builder Template"),
 *   base_table = "node_layout_builder_template",
 *   entity_keys = {
 *     "id" = "id",
 *   },
 * )
 */
class NodeLayoutBuilderTemplate extends ContentEntityBase implements ContentEntityInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the template entity.'))
      ->setReadOnly(TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The title of template.'))
      ->setReadOnly(TRUE);

    $fields['preview'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Preview'))
      ->setDescription(t('Image preview of template.'))
      ->setReadOnly(TRUE);

    $fields['data'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Data'))
      ->setDescription(t('Data of template.'))
      ->setReadOnly(TRUE);

    return $fields;
  }

}
