<?php

namespace Drupal\points\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the point_movement entity class.
 *
 * @ContentEntityType(
 *   id = "point_movement",
 *   label = @Translation("Point movement"),
 *   handlers = {
 *     "views_data" = "Drupal\points\PointMovementViewsData",
 *     "list_builder" = "Drupal\points\PointMovementListBuilder",
 *   },
 *   admin_permission = "administer point entities",
 *   fieldable = TRUE,
 *   base_table = "point_movement",
 *   entity_keys = {
 *     "id" = "mid",
 *     "uuid" = "uuid",
 *   },
 * )
 */
class PointMovement extends ContentEntityBase implements PointMovementInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['point_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Point'))
      ->setDescription(t('The parent point.'))
      ->setSetting('target_type', 'point')
      ->setReadOnly(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['points'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Points changed'))
      ->setDescription(t('This is a number that records points of this movement.'))
      ->setSetting('unsigned', FALSE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User'))
      ->setDescription(t('The user who operated on the point.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setReadOnly(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the movement happened.'));

    $fields['description'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Description'))
      ->setDescription(t('The description of this movement.'))
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
