<?php

namespace Drupal\mailjet_event\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\mailjet_event\EventInterface;
use Drupal\user\UserInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use MailJet\MailJet;

/**
 * Defines the Event entity.
 *
 * @ingroup event_entity
 *
 *
 * @ContentEntityType(
 *   id = "event_entity",
 *   label = @Translation("Mailjet event"),
 *   list_cache_contexts = { "user" },
 *   base_table = "mailjet_event",
 *   admin_permission = TRUE,
 *   entity_keys = {
 *     "id" = "event_id",
 *     "uuid" = "uuid"
 *   },
 * )
 *
 */
class Event extends ContentEntityBase implements EventInterface {

  use EntityChangedTrait;

  /*
   * {@inheritdoc}
   *
   * When a new entity instance is added, set the user_id entity reference to
   * the current user as the creator of the instance.
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getChangedTime() {
    return $this->get('changed')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   *
   * Define the field properties here.
   *
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    // Standard field, used as unique if primary index.
    $fields['event_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Event.'))
      ->setReadOnly(TRUE);

    // Standard field, unique outside of the scope of the current project.
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Event.'))
      ->setReadOnly(TRUE);

    $fields['event_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Event type - String '))
      ->setDescription(t('Event type - String'))
      ->setReadOnly(TRUE);

    $fields['event_field'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Event'))
      ->setDescription(t('Event description'))
      ->setRevisionable(TRUE)
      ->setDefaultValue([]);


    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The language code of  Campaign.'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the  Campaign entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the  Campaign entity was last edited.'));

    return $fields;
  }

}

