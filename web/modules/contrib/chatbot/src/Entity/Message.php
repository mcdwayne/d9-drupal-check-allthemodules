<?php

namespace Drupal\chatbot\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Message entity.
 *
 * @ingroup chatbot
 *
 * @ContentEntityType(
 *   id = "chatbot_message",
 *   label = @Translation("Message"),
 *   bundle_label = @Translation("Message type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\chatbot\MessageListBuilder",
 *     "views_data" = "Drupal\chatbot\Entity\MessageViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\chatbot\Form\MessageForm",
 *       "add" = "Drupal\chatbot\Form\MessageForm",
 *       "edit" = "Drupal\chatbot\Form\MessageForm",
 *       "delete" = "Drupal\chatbot\Form\MessageDeleteForm",
 *     },
 *     "access" = "Drupal\chatbot\MessageAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\chatbot\MessageHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "chatbot_message",
 *   admin_permission = "administer message entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/chatbots/chatbot_message/{chatbot_message}",
 *     "add-page" = "/admin/structure/chatbots/chatbot_message/add",
 *     "add-form" = "/admin/structure/chatbots/chatbot_message/add/{chatbot_message_type}",
 *     "edit-form" = "/admin/structure/chatbots/chatbot_message/{chatbot_message}/edit",
 *     "delete-form" = "/admin/structure/chatbots/chatbot_message/{chatbot_message}/delete",
 *     "collection" = "/admin/structure/chatbots/chatbot_message",
 *   },
 *   bundle_entity_type = "chatbot_message_type",
 *   field_ui_base_route = "entity.chatbot_message_type.edit_form"
 * )
 */
class Message extends ContentEntityBase implements MessageInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
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
  public function getTitle() {
    return $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle($title) {
    $this->set('title', $title);
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

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The title of the Message entity.'))
      ->setSettings([
        'max_length' => 255,
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
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Message is published.'))
      ->setDefaultValue(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
