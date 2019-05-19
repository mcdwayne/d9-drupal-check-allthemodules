<?php

namespace Drupal\slack_rtm\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Slack RTM Message entity.
 *
 * @ingroup slack_rtm
 *
 * @ContentEntityType(
 *   id = "slack_rtm_message",
 *   label = @Translation("Slack RTM Message"),
 *   label_singular = @Translation("Slack RTM Message"),
 *   label_plural = @Translation("Slack RTM Messages"),
 *   label_count = @PluralTranslation(
 *     singular = "@count Slack RTM Message",
 *     plural = "@count Slack RTM Messages"
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\slack_rtm\SlackRtmMessageListBuilder",
 *     "views_data" = "Drupal\slack_rtm\Entity\SlackRtmMessageViewsData",
 *     "translation" = "Drupal\slack_rtm\SlackRtmMessageTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\slack_rtm\Form\SlackRtmMessageForm",
 *       "delete" = "Drupal\slack_rtm\Form\SlackRtmMessageDeleteForm",
 *     },
 *     "access" = "Drupal\slack_rtm\SlackRtmMessageAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\slack_rtm\SlackRtmMessageHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "slack_rtm_message",
 *   data_table = "slack_rtm_message_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer slack rtm messages",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/slack_rtm_message/{slack_rtm_message}",
 *     "delete-form" = "/admin/structure/slack_rtm_message/{slack_rtm_message}/delete",
 *     "collection" = "/admin/structure/slack_rtm_message",
 *   },
 *   field_ui_base_route = "entity.slack_rtm_message.collection"
 * )
 */
class SlackRtmMessage extends ContentEntityBase implements SlackRtmMessageInterface {

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
  public function getChannel() {
    return $this->get('channel')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setChannel($channel) {
    $this->set('channel', $channel);
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
    $this->set('message', $message);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPermaLink() {
    return $this->get('permalink')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setPermaLink($link) {
    $this->set('permalink', $link);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessageAuthor() {
    return $this->get('message_author')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setMessageAuthor($msg_author) {
    $this->set('message_author', $msg_author);
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
  public function getTid() {
    return $this->get('tid')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTid($tid) {
    $this->set('tid', $tid);
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
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Slack RTM Message entity.'))
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
      ->setDescription(t('The name of the Slack RTM Message entity.'))
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

    $fields['channel'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Channel'))
      ->setDescription(t('The channel the Slack RTM Message originated from.'))
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

    $fields['message'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Message'))
      ->setDescription(t('The Slack RTM Message.'))
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string_long',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['permalink'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Message Permalink'))
      ->setDescription(t('The permalink to the message on Slack.'));

    $fields['message_author'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Message Author'))
      ->setDescription(t('The author of the message on Slack.'))
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

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'datetime',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => -4,
        'settings' => [
          'format_type' => 'long',
          'timezone_override' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tid'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Timestamp ID'))
      ->setDescription(t('The Slack RTM Message creation full ts.'));

    return $fields;
  }

}
