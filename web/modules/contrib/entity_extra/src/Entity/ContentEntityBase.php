<?php

namespace Drupal\entity_extra\Entity;

use Drupal\Core\Entity\ContentEntityBase as CoreContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * An entity type base class that already defines fields for id, creation and 
 * update dates, universally unique ID, label and owner.
 *
 * The fields names are taken from the entity keys declared in the entity 
 * type's annotation.
 *
 * Besides the default entity keys, the following keys are added:
 * - created: The creation date.
 * - changed: The date when the entity was last updated.
 * - owner: The user who owns the entity.
 */
class ContentEntityBase extends CoreContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Fields for extra entity keys.
    if ($entity_type->hasKey('label')) {
      $field_name = $entity_type->getKey('label');
      $fields[$field_name] = BaseFieldDefinition::create('string')
        ->setLabel(t('Label'))
        ->setDescription(t('The entity label.'))
        ->setSettings([
          'default_value' => '',
          'max_length' => 255,
          'text_processing' => 0,
        ])
        ->setDisplayOptions('view', [
          'label' => 'hidden',
          'type' => 'string',
          'weight' => -10,
        ])
        ->setDisplayOptions('form', [
          'type' => 'string_textfield',
          'weight' => -10,
        ])
        ->setRequired(TRUE)
        ->setRevisionable(TRUE)
        ->setDisplayConfigurable('form', TRUE)
        ->setDisplayConfigurable('view', TRUE);
    }
    if ($entity_type->hasKey('created')) {
      $field_name = $entity_type->getKey('created');
      $fields[$field_name] = BaseFieldDefinition::create('created')
        ->setLabel(t('Created'))
        ->setDescription(t('When the entity was created.'))
        ->setDisplayConfigurable('view', TRUE)
        ->setReadOnly(TRUE);
    }
    if ($entity_type->hasKey('changed')) {
      $field_name = $entity_type->getKey('changed');
      $fields[$field_name] = BaseFieldDefinition::create('changed')
        ->setLabel(t('Changed'))
        ->setDescription(t('When the entity was last updated.'))
        ->setDisplayConfigurable('view', TRUE)
        ->setReadOnly(TRUE);
    }
    if ($entity_type->hasKey('owner')) {
      $field_name = $entity_type->getKey('owner');
      $fields[$field_name] = BaseFieldDefinition::create('entity_reference')
        ->setLabel(t('Owner'))
        ->setDescription(t("The entity's owner."))
        ->setRequired(TRUE)
        ->setRevisionable(TRUE)
        ->setSetting('target_type', 'user')
        ->setDisplayConfigurable('view', TRUE);
    }

    return $fields;
  }

  /**
   * {@inheridoc}
   */
  public static function preCreate(EntityStorageInterface $storage, array &$values) {
    parent::preCreate($storage, $values);

    // If the entity has an owner, it defaults to the current user.
    $entity_type = $storage->getEntityType();
    if ($entity_type->hasKey('owner')) {
      $field_name = $entity_type->getKey('owner');
      $values += [
        $field_name => \Drupal::currentUser()->id(),
      ];
    }
  }

}
