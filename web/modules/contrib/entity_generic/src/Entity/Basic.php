<?php

namespace Drupal\entity_generic\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Session\AccountInterface;

/**
 * Implements basic entity class.
 */
abstract class Basic extends ContentEntityBase implements BasicInterface {

  use EntityCreatedTrait;
  use EntityChangedTrait;
  use EntityArchivedTrait;
  use EntityDeletedTrait;
  use EntityApprovedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields = parent::baseFieldDefinitions($entity_type);

    if ($entity_type->hasKey('created')) {
      $fields[$entity_type->getKey('created')] = BaseFieldDefinition::create('created')
        ->setLabel(t('Created'))
        ->setDisplayOptions('form', array(
          'type' => 'datetime_timestamp',
          'weight' => 10,
        ))
        ->setDisplayConfigurable('form', TRUE)
        ->setDisplayOptions('view', array(
          'label' => 'hidden',
          'type' => 'timestamp',
          'weight' => 10,
        ))
        ->setDisplayConfigurable('view', TRUE);
      if ($entity_type->isRevisionable()) {
        $fields[$entity_type->getKey('created')]->setRevisionable(TRUE);
      }
    }

    if ($entity_type->hasKey('changed')) {
      $fields['changed'] = BaseFieldDefinition::create('changed')
        ->setLabel(t('Changed'))
        ->setDisplayOptions('view', array(
          'label' => 'hidden',
          'type' => 'timestamp',
          'weight' => 20,
        ))
        ->setDisplayConfigurable('view', TRUE);
    }

    // Archived field.
    if($entity_type->hasKey('archived')) {
      $fields[$entity_type->getKey('archived')] = BaseFieldDefinition::create('boolean')
        ->setLabel(t('Archived'))
        ->setTranslatable(FALSE)
        ->setRevisionable(FALSE)
        ->setDefaultValue(FALSE)
        ->setStorageRequired(TRUE)
        ->setSettings([
          'on_label' => t('Archived'),
          'off_label' => t('Not archived'),
        ])
        ->setDisplayOptions('form', array(
          'type' => 'boolean_checkbox',
          'settings' => array(
            'display_label' => TRUE,
          ),
          'weight' => 0,
        ))
        ->setDisplayConfigurable('form', TRUE);

      $fields[$entity_type->getKey('archived') . '_time'] = BaseFieldDefinition::create('timestamp')
        ->setLabel(t('Archived time'))
        ->setDescription(t('Date and time the entity archived.'))
        ->setTranslatable(FALSE)
        ->setRevisionable(FALSE)
        ->setDisplayOptions('form', [
          'type' => 'datetime_timestamp',
          'weight' => 0,
        ])
        ->setDisplayConfigurable('form', TRUE)
        ->setDisplayOptions('view', [
          'label' => 'inline',
          'type' => 'timestamp',
          'weight' => 0,
        ])
        ->setDisplayConfigurable('view', TRUE);
    }

    // Deleted field.
    if($entity_type->hasKey('flag_deleted')) {
      $fields[$entity_type->getKey('flag_deleted')] = BaseFieldDefinition::create('boolean')
        ->setLabel(t('Deleted'))
        ->setTranslatable(FALSE)
        ->setRevisionable(FALSE)
        ->setDefaultValue(FALSE)
        ->setStorageRequired(TRUE)
        ->setSettings([
          'on_label' => t('Deleted'),
          'off_label' => t('Not deleted'),
        ])
        ->setDisplayOptions('form', array(
          'type' => 'boolean_checkbox',
          'settings' => array(
            'display_label' => TRUE,
          ),
          'weight' => 0,
        ))
        ->setDisplayConfigurable('form', TRUE);

      $fields[$entity_type->getKey('flag_deleted') . '_time'] = BaseFieldDefinition::create('timestamp')
        ->setLabel(t('Deleted time'))
        ->setDescription(t('Date and time the entity flagged as deleted.'))
        ->setTranslatable(FALSE)
        ->setRevisionable(FALSE)
        ->setDisplayOptions('form', [
          'type' => 'datetime_timestamp',
          'weight' => 0,
        ])
        ->setDisplayConfigurable('form', TRUE)
        ->setDisplayOptions('view', [
          'label' => 'inline',
          'type' => 'timestamp',
          'weight' => 0,
        ])
        ->setDisplayConfigurable('view', TRUE);
    }

    // Approved field.
    if($entity_type->hasKey('approved')) {
      $fields[$entity_type->getKey('approved')] = BaseFieldDefinition::create('boolean')
        ->setLabel(t('Approved'))
        ->setTranslatable(FALSE)
        ->setRevisionable(FALSE)
        ->setDefaultValue(FALSE)
        ->setStorageRequired(TRUE)
        ->setSettings([
          'on_label' => t('Approved'),
          'off_label' => t('Not approved'),
        ])
        ->setDisplayOptions('form', array(
          'type' => 'boolean_checkbox',
          'settings' => array(
            'display_label' => TRUE,
          ),
          'weight' => 0,
        ))
        ->setDisplayConfigurable('form', TRUE);

      $fields[$entity_type->getKey('approved') . '_time'] = BaseFieldDefinition::create('timestamp')
        ->setLabel(t('Approved time'))
        ->setDescription(t('Date and time the entity approved.'))
        ->setTranslatable(FALSE)
        ->setRevisionable(FALSE)
        ->setDisplayOptions('form', [
          'type' => 'datetime_timestamp',
          'weight' => 0,
        ])
        ->setDisplayConfigurable('form', TRUE)
        ->setDisplayOptions('view', [
          'label' => 'inline',
          'type' => 'timestamp',
          'weight' => 0,
        ])
        ->setDisplayConfigurable('view', TRUE);
    }

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function access($operation = 'view', AccountInterface $account = NULL, $return_as_object = FALSE) {
    // This override exists to set the operation to the default value "view".
    return parent::access($operation, $account, $return_as_object);
  }

}
