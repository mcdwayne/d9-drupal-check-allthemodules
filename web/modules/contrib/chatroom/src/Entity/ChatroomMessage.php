<?php


namespace Drupal\chatroom\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;

/**
 * Defines the chatroom message entity class.
 *
 * @ContentEntityType(
 *   id = "chatroom_message",
 *   label = @Translation("Chatroom message"),
 *   handlers = {
 *     "views_data" = "Drupal\chatroom\ChatroomViewsData",
 *   },
 *   base_table = "chatroom_message",
 *   entity_keys = {
 *     "id" = "cmid",
 *     "label" = "text",
 *     "uid" = "uid",
 *     "cid" = "cid",
 *     "uuid" = "uuid",
 *   },
 *   render_cache = FALSE,
 * )
 */
class ChatroomMessage extends ContentEntityBase {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = [];
    $fields['cmid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Chatroom message ID'))
      ->setDescription(t('The chatroom message ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The chatroom message UUID.'))
      ->setReadOnly(TRUE);

    $fields['cid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Chatroom'))
      ->setDescription(t('The chatroom that this message belongs to.'))
      ->setSetting('target_type', 'chatroom')
      ->setRequired(TRUE);

    $fields['text'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Text'))
      ->setDescription(t('The message text.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 1000)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'string',
      ));

    $fields['anon_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Anonymous name'))
      ->setDescription(t('Sender name, if anonymous.'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'string',
      ));

    $fields['type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Message type'))
      ->setDescription(t('The type of this item: message or command.'))
      ->setSetting('max_length', 32);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The username of the message author.'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback('Drupal\chatroom\Entity\ChatroomMessage::getCurrentUserId')
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'author',
      ));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created on'))
      ->setDescription(t('The timestamp of the message creation date.'))
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'timestamp',
      ));

    return $fields;
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
    return [\Drupal::currentUser()->id()];
  }

}
