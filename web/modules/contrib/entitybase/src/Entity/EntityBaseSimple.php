<?php

/**
 * @file
 * Contains \Drupal\entity_base\EntityBaseSimple.
 */

namespace Drupal\entity_base\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\UserInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\entity_base\Exception\LockException;
use Drupal\Component\Render\FormattableMarkup;

/**
 * Implements enhancements to the Entity class.
 *
 * @ingroup entity_api
 */
abstract class EntityBaseSimple extends EntityBaseBasic implements EntityBaseSimpleInterface {

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    // Handle workflows.
    $entityDefinition = $this->entityTypeManager()->getDefinition($this->getEntityTypeId());

    // Handle "Current" workflow
    if (isset($entityDefinition->get('additional')['entity_base']['workflows']['current']) && $entityDefinition->get('additional')['entity_base']['workflows']['current']) {
      if ($this->get('current')->value) {
        $entities = $this->entityTypeManager()->getStorage($this->getEntityTypeId())->loadByProperties(['current' => TRUE]);
        foreach ($entities as $entity) {
          $entity->set('current', FALSE);
          $entity->save();
        }
      }
    }

    parent::preSave($storage);
  }

  /**
   * {@inheritdoc}
   */
  public function lock() {
    if (!\Drupal::service('lock.persistent')->acquire("{$this->getEntityTypeId()}_{$this->id()}", 3600 * 12)) {
      $args = ['@entity_type_id' => $this->getEntityTypeId(), '@id' => $this->id()];
      throw new LockException(new FormattableMarkup('Cannot acquire lock for @entity_type_id : @id.', $args));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function unlock() {
    \Drupal::service('lock.persistent')->release("{$this->getEntityTypeId()}_{$this->id()}");
  }

  /**
   * {@inheritdoc}
   */
  public function isLocked() {
    return !\Drupal::service('lock.persistent')->lockMayBeAvailable("{$this->getEntityTypeId()}_{$this->id()}");
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Revision field.
    if($entity_type->get('entity_keys')['revision']) {
      $fields[$entity_type->get('entity_keys')['revision']] = BaseFieldDefinition::create('integer')
        ->setLabel(t('Revision ID'))
        ->setDescription(t('The entity revision ID.'))
        ->setReadOnly(TRUE)
        ->setSetting('unsigned', TRUE);
    }

    // Status field.
    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Entity status'))
      ->setDescription(t('A boolean indicating whether the entity is active.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
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

    // Name field.
    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('Name of the object.'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
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

    // Additional entity info data.
    $additional = $entity_type->get('additional');

    // Current field.
    if (isset($additional['entity_base']['workflows']['current']) && $additional['entity_base']['workflows']['current'] === TRUE && isset($entity_type->get('entity_keys')['current'])) {
      $fields['current'] = BaseFieldDefinition::create('boolean')
        ->setLabel(t('Current'))
        ->setDescription(t('Current active entity.'))
        ->setDefaultValue(FALSE)
        ->setRevisionable(TRUE)
        ->setDisplayOptions('view', array(
          'label' => 'hidden',
          'type' => 'boolean',
          'weight' => 0,
        ))
        ->setDisplayConfigurable('view', TRUE)
        ->setDisplayOptions('form', array(
          'settings' => array('display_label' => TRUE),
          'weight' => 0,
        ))
        ->setDisplayConfigurable('form', TRUE);
    }

    // Locked field.
    if (isset($additional['entity_base']['workflows']['locked']) && $additional['entity_base']['workflows']['locked'] === TRUE && isset($entity_type->get('entity_keys')['locked'])) {
      $fields['locked'] = BaseFieldDefinition::create('boolean')
        ->setLabel(t('Locked'))
        ->setDescription(t('Locked entity.'))
        ->setDefaultValue(FALSE)
        ->setRevisionable(TRUE)
        ->setDisplayOptions('view', array(
          'label' => 'hidden',
          'type' => 'boolean',
          'weight' => 0,
        ))
        ->setDisplayConfigurable('view', TRUE)
        ->setDisplayOptions('form', array(
          'settings' => array('display_label' => TRUE),
          'weight' => 0,
        ))
        ->setDisplayConfigurable('form', TRUE);
    }

    // Queued field.
    if (isset($additional['entity_base']['workflows']['queued']) && $additional['entity_base']['workflows']['queued'] === TRUE && isset($entity_type->get('entity_keys')['queued'])) {
      $fields['queued'] = BaseFieldDefinition::create('timestamp')
        ->setLabel(t('Queued'))
        ->setDescription(t('Time when this feed was queued for refresh, 0 if not queued.'))
        ->setDefaultValue(0);
    }

    // Processed field.
    if (isset($additional['entity_base']['workflows']['processed']) && $additional['entity_base']['workflows']['processed'] === TRUE && isset($entity_type->get('entity_keys')['processed'])) {
      $fields['processed'] = BaseFieldDefinition::create('boolean')
        ->setLabel(t('Processed'))
        ->setDescription(t('Processed entity.'))
        ->setDefaultValue(FALSE)
        ->setRevisionable(TRUE)
        ->setDisplayOptions('view', array(
          'label' => 'hidden',
          'type' => 'boolean',
          'weight' => 0,
        ))
        ->setDisplayConfigurable('view', TRUE)
        ->setDisplayOptions('form', array(
          'settings' => array('display_label' => TRUE),
          'weight' => 0,
        ))
        ->setDisplayConfigurable('form', TRUE);
    }

    // Title field.
    if (isset($additional['entity_base']['workflows']['title']) && $additional['entity_base']['workflows']['title'] === TRUE && isset($entity_type->get('entity_keys')['title'])) {
      $fields['title'] = BaseFieldDefinition::create('string')
        ->setLabel(t('Title'))
        ->setDescription(t('Displayable title of the object.'))
        ->setRequired(TRUE)
        ->setTranslatable(TRUE)
        ->setRevisionable(TRUE)
        ->setSetting('max_length', 255)
        ->setDisplayOptions('view', array(
          'label' => 'hidden',
          'type' => 'string',
          'weight' => -4,
        ))
        ->setDisplayConfigurable('view', TRUE)
        ->setDisplayOptions('form', array(
          'type' => 'string_textfield',
          'weight' => -4,
        ))
        ->setDisplayConfigurable('form', TRUE);
    }

    // Code field.
    if (isset($additional['entity_base']['workflows']['code']) && $additional['entity_base']['workflows']['code'] === TRUE && isset($entity_type->get('entity_keys')['code'])) {
      $fields['code'] = BaseFieldDefinition::create('string')
        ->setLabel(t('Code'))
        ->setDescription(t('The code of this object.'))
        ->setRequired(TRUE)
        ->setTranslatable(FALSE)
        ->setRevisionable(FALSE)
        ->setSetting('max_length', 64)
        ->setDisplayOptions('view', array(
          'label' => 'inline',
          'type' => 'string',
          'weight' => -3,
        ))
        ->setDisplayConfigurable('view', TRUE)
        ->setDisplayOptions('form', array(
          'type' => 'string_textfield',
          'weight' => -3,
        ))
        ->setDisplayConfigurable('form', TRUE);
    }

    // Owner UID field.
    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Owner'))
      ->setDescription(t('The username of the entity owner.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\entity_base\Entity\EntityBaseSimple::getCurrentUserId')
      ->setDisplayOptions('view', array(
        'label' => 'inline',
        'type' => 'author',
        'weight' => 0,
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

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->getEntityKey('label');
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->getEntityKey('label');
  }

  /**
   * {@inheritdoc}
   */
  public function setLabel($label) {
    $this->set('name', $label);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->getEntityKey('uid');
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isActive() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setActive($active) {
    $this->set('status', $active ? 1 : 0);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionCreationTime() {
    return $this->get('revision_timestamp')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionCreationTime($timestamp) {
    $this->set('revision_timestamp', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionAuthor() {
    return $this->get('revision_uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionAuthorId($uid) {
    $this->set('revision_uid', $uid);
    return $this;
  }

  /**
   * Default value callback for 'uid' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getCurrentUserId() {
    return array(\Drupal::currentUser()->id());
  }

}
