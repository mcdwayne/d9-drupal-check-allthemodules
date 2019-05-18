<?php

namespace Drupal\chat_channels\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Chat channel entity.
 *
 * @ingroup chat_channels
 *
 * @ContentEntityType(
 *   id = "chat_channel",
 *   label = @Translation("Chat channel"),
 *   bundle_label = @Translation("Chat channel type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\chat_channels\ChatChannelListBuilder",
 *     "views_data" = "Drupal\chat_channels\Entity\ChatChannelViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\chat_channels\Form\ChatChannelForm",
 *       "add" = "Drupal\chat_channels\Form\ChatChannelForm",
 *       "edit" = "Drupal\chat_channels\Form\ChatChannelForm",
 *       "delete" = "Drupal\chat_channels\Form\ChatChannelDeleteForm",
 *     },
 *     "access" = "Drupal\chat_channels\ChatChannelAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\chat_channels\ChatChannelHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "chat_channel",
 *   admin_permission = "administer chat channel entities",
 *   entity_keys = {
 *     "id" = "eid",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "uid",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/chat_channel/chat_channel/{chat_channel}",
 *     "add-page" = "/admin/chat_channel/chat_channel/add",
 *     "add-form" = "/admin/chat_channel/chat_channel/add/{chat_channel_type}",
 *     "edit-form" = "/admin/chat_channel/chat_channel/{chat_channel}/edit",
 *     "delete-form" = "/admin/chat_channel/chat_channel/{chat_channel}/delete",
 *     "collection" = "/admin/chat_channel/chat_channel",
 *   },
 *   bundle_entity_type = "chat_channel_type",
 *   field_ui_base_route = "entity.chat_channel_type.edit_form"
 * )
 */
class ChatChannel extends ContentEntityBase implements ChatChannelInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'uid' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->bundle();
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
  public function getUser() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getUserId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setUserId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setUser(UserInterface $account) {
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
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = [];

    $fields['eid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Channel ID'))
      ->setDescription(t('The channel ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Created by'))
      ->setDescription(t('The user ID of author of the Chat channel entity.'))
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

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The channel UUID.'))
      ->setReadOnly(TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Chat channel entity.'))
      ->setSettings(array(
        'max_length' => 255,
        'text_processing' => 0,
      ))
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
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Active status'))
      ->setDescription(t('A boolean indicating whether the Chat channel is active.'))
      ->setDefaultValue(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    if ($bundle_entity_type_id = $entity_type->getBundleEntityType()) {
      $fields[$entity_type->getKey('bundle')] = BaseFieldDefinition::create('entity_reference')
        ->setLabel($entity_type->getBundleLabel())
        ->setSetting('target_type', $bundle_entity_type_id)
        ->setRequired(TRUE)
        ->setReadOnly(TRUE);
    }
    else {
      $fields[$entity_type->getKey('bundle')] = BaseFieldDefinition::create('string')
        ->setLabel($entity_type->getBundleLabel())
        ->setRequired(TRUE)
        ->setReadOnly(TRUE);
    }

    return $fields;
  }

}
