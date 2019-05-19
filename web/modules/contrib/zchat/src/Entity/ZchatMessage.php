<?php

namespace Drupal\zchat\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Zchat Message entity.
 *
 * @ingroup zchat
 *
 * @ContentEntityType(
 *   id = "zchatmessage",
 *   label = @Translation("Zchat Message"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\zchat\ZchatMessageListBuilder",
 *     "views_data" = "Drupal\zchat\Entity\ZchatMessageViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\zchat\Form\ZchatMessageForm",
 *       "add" = "Drupal\zchat\Form\ZchatMessageForm",
 *       "edit" = "Drupal\zchat\Form\ZchatMessageForm",
 *       "delete" = "Drupal\zchat\Form\ZchatMessageDeleteForm",
 *     },
 *     "access" = "Drupal\zchat\ZchatMessageAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\zchat\ZchatMessageHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "zchatmessage",
 *   admin_permission = "administer zchat message entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uid" = "user_id",
 *     "status" = "status",
 *     "message_text" = "message_text",
 *      "created_ms" = "created_ms",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/zchatmessage/{zchatmessage}",
 *     "add-form" = "/admin/structure/zchatmessage/add",
 *     "edit-form" = "/admin/structure/zchatmessage/{zchatmessage}/edit",
 *     "delete-form" = "/admin/structure/zchatmessage/{zchatmessage}/delete",
 *     "collection" = "/admin/structure/zchatmessage",
 *   },
 *   field_ui_base_route = "zchatmessage.settings"
 * )
 */
class ZchatMessage extends ContentEntityBase implements ZchatMessageInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
      'created_ms' => round(\Drupal::time()->getRequestMicroTime() * 1000),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessageText() {
    return $this->get('message_text')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setMessageText($message_text) {
    $this->set('message_text', $message_text);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedMs() {
    return $this->get('created_ms')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedMs($created_ms) {
    $this->set('created_ms', $created_ms);
    return $this;
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
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
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
   */
  public function isPublished() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->set('status', $published ? TRUE : FALSE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Zchat Message entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Zchat Message entity.'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Zchat Message is published.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -3,
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['message_text'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Message Text'))
      ->setDescription(t('Message Text'))
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 5000,
        'text_processing' => 0,
      ));

    $fields['created_ms'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Created Milliseconds'))
      ->setDescription(t('Created Milliseconds'))
      ->setSettings(array(
        'size' => 'big',
      ));

    return $fields;
  }

}
