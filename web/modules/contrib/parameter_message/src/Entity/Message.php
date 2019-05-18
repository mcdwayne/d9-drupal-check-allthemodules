<?php

namespace Drupal\parameter_message\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\parameter_message\ParameterMessageInterface;
use Drupal\user\UserInterface;
use Drupal\Core\Entity\EntityChangedTrait;

/**
 * Defines the Message entity.
 *
 * @ingroup parameter_message
 *
 * @ContentEntityType(
 *   id = "parameter_message_message",
 *   label = @Translation("Message entity"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\parameter_message\Entity\Controller\MessageListBuilder",
 *     "form" = {
 *       "add" = "Drupal\parameter_message\Form\MessageForm",
 *       "edit" = "Drupal\parameter_message\Form\MessageForm",
 *       "delete" = "Drupal\parameter_message\Form\MessageDeleteForm",
 *     },
 *     "access" = "Drupal\parameter_message\MessageAccessControlHandler",
 *   },
 *   list_cache_contexts = { "user" },
 *   base_table = "message",
 *   admin_permission = "administer parameter_message entity",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "parameter",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/parameter_message_message/{parameter_message_message}",
 *     "edit-form" = "/parameter_message_message/{parameter_message_message}/edit",
 *     "delete-form" = "/parameter_message_message/{parameter_message_message}/delete",
 *     "collection" = "/parameter_message_message"
 *   },
 *   field_ui_base_route = "parameter_message.settings",
 * )
 */
class Message extends ContentEntityBase implements ParameterMessageInterface {

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
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Message entity.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Message entity.'))
      ->setReadOnly(TRUE);

    $parameter_description = t('The Parameter for show the Message. Example: welcome');
    $parameter_description .= '<ul>';
    $parameter_description .= '<li>/home?message=welcome</li>';
    $parameter_description .= '</ul>';

    $fields['parameter'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Parameter'))
      ->setDescription($parameter_description)
      ->setRequired(TRUE)
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue(NULL)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -6,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['body'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Body'))
      ->setDescription(t('The body of the Message Entity.'))
      ->setRequired(TRUE)
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue(NULL)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => -5,
        'settings' => [
          'rows' => 11,
        ],
      ])

      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Type'))
      ->setRequired(TRUE)
      ->setSettings([
        'allowed_values' => [
          'status' => 'Status',
          'warning' => 'Warning',
          'error' => 'Error',
        ],
      ])
      ->setDefaultValue('page')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The language code of Message entity.'));
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
