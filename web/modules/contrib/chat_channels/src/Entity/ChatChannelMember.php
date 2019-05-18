<?php

namespace Drupal\chat_channels\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Chat channel member entity.
 *
 * @ingroup chat_channels
 *
 * @ContentEntityType(
 *   id = "chat_channel_member",
 *   label = @Translation("Chat channel member"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\chat_channels\ChatChannelMemberListBuilder",
 *     "views_data" = "Drupal\chat_channels\Entity\ChatChannelMemberViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\chat_channels\Form\ChatChannelMemberForm",
 *       "add" = "Drupal\chat_channels\Form\ChatChannelMemberForm",
 *       "edit" = "Drupal\chat_channels\Form\ChatChannelMemberForm",
 *       "delete" = "Drupal\chat_channels\Form\ChatChannelMemberDeleteForm",
 *     },
 *     "access" = "Drupal\chat_channels\ChatChannelMemberAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\chat_channels\ChatChannelMemberHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "chat_channel_member",
 *   admin_permission = "administer chat channel member entities",
 *   entity_keys = {
 *     "id" = "eid",
 *     "uid" = "uid",
 *
 *   },
 *   links = {
 *     "canonical" = "/admin/chat_channel/chat_channel_member/{chat_channel_member}",
 *     "add-form" = "/admin/chat_channel/chat_channel_member/add",
 *     "edit-form" = "/admin/chat_channel/chat_channel_member/{chat_channel_member}/edit",
 *     "delete-form" = "/admin/chat_channel/chat_channel_member/{chat_channel_member}/delete",
 *     "collection" = "/admin/chat_channel/chat_channel_member",
 *   },
 *   field_ui_base_route = "chat_channel_member.settings"
 * )
 */
class ChatChannelMember extends ContentEntityBase implements ChatChannelMemberInterface {

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
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('member_since', $timestamp);
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
    $this->set('status', $active ? TRUE : FALSE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getChannelId() {
    return $this->get('channel')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setChannelId($cid) {
    return  $this->set('channel', $cid);
  }

  /**
   * {@inheritdoc}
   */
  public function getLastSeenMessageId() {
    return $this->get('last_seen_message')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setLastSeenMessageId($LastSeenMessageId) {
    return  $this->set('last_seen_message', $LastSeenMessageId);
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
      ->setDescription(t('The user ID connected to the Chat channel member entity.'))
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

    $fields['channel'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Channel id'))
      ->setDescription(t('The channel id connected to the Chat channel member entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'chat_channel')
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

    $fields['last_seen_message'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Message id'))
      ->setDescription(t('The chat channel message id connected to the Chat channel member entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'chat_channel_message')
      ->setSetting('handler', 'default');

    $fields['member_since'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Member since'))
      ->setDescription(t('The time that the entity was created.'));

    return $fields;
  }

}
