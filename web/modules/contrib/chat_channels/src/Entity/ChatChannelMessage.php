<?php

namespace Drupal\chat_channels\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Chat channel message entity.
 *
 * @ingroup chat_channels
 *
 * @ContentEntityType(
 *   id = "chat_channel_message",
 *   label = @Translation("Chat channel message"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\chat_channels\ChatChannelMessageListBuilder",
 *     "views_data" = "Drupal\chat_channels\Entity\ChatChannelMessageViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\chat_channels\Form\ChatChannelMessageForm",
 *       "add" = "Drupal\chat_channels\Form\ChatChannelMessageForm",
 *       "edit" = "Drupal\chat_channels\Form\ChatChannelMessageForm",
 *       "delete" = "Drupal\chat_channels\Form\ChatChannelMessageDeleteForm",
 *     },
 *     "access" = "Drupal\chat_channels\ChatChannelMessageAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\chat_channels\ChatChannelMessageHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "chat_channel_message",
 *   admin_permission = "administer chat channel message entities",
 *   entity_keys = {
 *     "id" = "eid",
 *     "uid" = "user_id",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/chat_channel/chat_channel_message/{chat_channel_message}",
 *     "add-form" = "/admin/chat_channel/chat_channel_message/add",
 *     "edit-form" = "/admin/chat_channel/chat_channel_message/{chat_channel_message}/edit",
 *     "delete-form" = "/admin/chat_channel/chat_channel_message/{chat_channel_message}/delete",
 *     "collection" = "/admin/chat_channel/chat_channel_message",
 *   },
 *   field_ui_base_route = "chat_channel_message.settings"
 * )
 */
class ChatChannelMessage extends ContentEntityBase implements ChatChannelMessageInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
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
  public function getOwner() {
    $uid = $this->get('uid')->target_id;

    /** @var \Drupal\user\UserStorageInterface $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('user');

    /** @var \Drupal\user\UserInterface $user */
    $user = $storage->load($uid);

    return $user;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('uid')->target_id;
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
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
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
  public function getMessage() {
    return $this->get('message')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setMessage($message) {
    $this->set('name', $message);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getChannelId() {
    return $this->get('channel')->value;
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
      ->setDescription(t('The user ID of author of the Chat channel message entity.'))
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

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Chat channel message is published.'))
      ->setDefaultValue(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

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

    $fields['message'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Message'))
      ->setDescription(t('The content of the chat channel message entity'))
      ->setTranslatable(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'text_default',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'text_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE);

    return $fields;
  }

}
