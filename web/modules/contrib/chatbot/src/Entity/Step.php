<?php

namespace Drupal\chatbot\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Step entity.
 *
 * @ingroup chatbot
 *
 * @ContentEntityType(
 *   id = "chatbot_step",
 *   label = @Translation("Step"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\chatbot\StepListBuilder",
 *     "views_data" = "Drupal\chatbot\Entity\StepViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\chatbot\Form\StepForm",
 *       "add" = "Drupal\chatbot\Form\StepForm",
 *       "edit" = "Drupal\chatbot\Form\StepForm",
 *       "delete" = "Drupal\chatbot\Form\StepDeleteForm",
 *     },
 *     "access" = "Drupal\chatbot\StepAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\chatbot\StepHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "chatbot_step",
 *   admin_permission = "administer step entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/chatbots/chatbot_step/{chatbot_step}",
 *     "add-form" = "/admin/structure/chatbots/chatbot_step/add",
 *     "edit-form" = "/admin/structure/chatbots/chatbot_step/{chatbot_step}/edit",
 *     "delete-form" = "/admin/structure/chatbots/chatbot_step/{chatbot_step}/delete",
 *     "collection" = "/admin/structure/chatbots/chatbot_step",
 *   },
 *   field_ui_base_route = "chatbot_step.settings"
 * )
 */
class Step extends ContentEntityBase implements StepInterface {

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

    $fields['messages'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Messages'))
      ->setDescription(t('Enter the messages in this step.'))
      ->setSetting('target_type', 'chatbot_message')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
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

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The title of the Step entity.'))
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
      ->setDescription(t('A boolean indicating whether the Step is published.'))
      ->setDefaultValue(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

  /**
   * Returns a list of Message Entity.
   *
   * @return \Drupal\chatbot\Entity\MessageInterface
   *   A list of Messages
   */
  public function getMessages() {
    return $this->get('messages')->value;
  }

  /**
   * Sets the entity owner's user entity.
   *
   * @param $messages
   *   A list of Messages
   *
   * @return $this
   */
  public function setMessages($messages) {
    $this->set('messages', $messages);
    return $this;
  }

}
