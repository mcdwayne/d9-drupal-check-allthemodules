<?php

namespace Drupal\entity_generic\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\RevisionLogEntityTrait;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\UserInterface;

/**
 * Implements simple entity class.
 */
abstract class Simple extends Basic implements SimpleInterface {

  use EntityOwnerTrait;
  use EntityLabelTrait;
  use EntityStatusTrait;
  use RevisionLogEntityTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Revision field.
    if($entity_type->isRevisionable()) {
      // Add the revision metadata fields.
      $fields += static::revisionLogBaseFieldDefinitions($entity_type);

      $fields[$entity_type->getKey('revision')] = BaseFieldDefinition::create('integer')
        ->setLabel(t('Revision ID'))
        ->setReadOnly(TRUE)
        ->setSetting('unsigned', TRUE);
    }

    // Status field.
    if($entity_type->hasKey('status')) {
      $fields[$entity_type->getKey('status')] = BaseFieldDefinition::create('boolean')
        ->setLabel(t('Status'))
        ->setDefaultValue(TRUE)
        ->setSettings([
          'on_label' => t('Enabled'),
          'off_label' => t('Disabled'),
        ])
        ->setDisplayOptions('form', array(
          'type' => 'boolean_checkbox',
          'settings' => array(
            'display_label' => TRUE,
          ),
          'weight' => 0,
        ))
        ->setDisplayConfigurable('form', TRUE);
      if ($entity_type->isRevisionable()) {
        $fields[$entity_type->getKey('status')]->setRevisionable(TRUE);
      }
    }

    // Label field.
    if($entity_type->hasKey('label') && $entity_type->getKey('label') != $entity_type->getKey('id')) {
      $fields[$entity_type->getKey('label')] = BaseFieldDefinition::create('string')
        ->setLabel(t('Label'))
        ->setRequired(TRUE)
        ->setSetting('max_length', 255)
        ->setDisplayOptions('view', array(
          'label' => 'hidden',
          'type' => 'string',
          'weight' => -5,
        ))
        ->setDisplayConfigurable('view', TRUE)
        ->setDisplayOptions('form', array(
          'type' => 'string_textfield',
          'weight' => -5,
        ))
        ->setDisplayConfigurable('form', TRUE);
      if ($entity_type->isRevisionable()) {
        $fields[$entity_type->getKey('label')]->setRevisionable(TRUE);
      }
      if ($entity_type->isTranslatable()) {
        $fields[$entity_type->getKey('label')]->setTranslatable(TRUE);
      }
    }

    // Owner UID field.
    if($entity_type->hasKey('uid')) {
      $fields[$entity_type->getKey('uid')] = BaseFieldDefinition::create('entity_reference')
        ->setLabel(t('Owner'))
        ->setSetting('target_type', 'user')
        ->setSetting('handler', 'default')
        ->setDefaultValueCallback('Drupal\entity_generic\Entity\Simple::getCurrentUserId')
        ->setDisplayOptions('view', array(
          'label' => 'inline',
          'type' => 'author',
          'weight' => 5,
        ))
        ->setDisplayConfigurable('view', TRUE)
        ->setDisplayOptions('form', array(
          'type' => 'entity_reference_autocomplete',
          'weight' => 5,
          'settings' => array(
            'match_operator' => 'CONTAINS',
            'size' => '60',
            'placeholder' => '',
          ),
        ))
        ->setDisplayConfigurable('form', TRUE);
      if ($entity_type->isRevisionable()) {
        $fields[$entity_type->getKey('uid')]->setRevisionable(TRUE);
      }
    }

    return $fields;
  }

  /**
   * Default value callback for 'uid' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return int[]
   *   An array of default values.
   */
  public static function getCurrentUserId() {
    return [\Drupal::currentUser()->id()];
  }

}
