<?php

namespace Drupal\mass_contact\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\user\UserInterface;

/**
 * The mass contact message entity for archiving messages.
 *
 * @ContentEntityType(
 *   id = "mass_contact_message",
 *   label = @Translation("Mass Contact Message"),
 *   label_singular = @Translation("mass contact message"),
 *   label_plural = @Translation("mass contact messages"),
 *   label_count = @PluralTranslation(
 *     singular = "@count mass contact message",
 *     plural = "@count mass contact messages"
 *   ),
 *   handlers = {
 *     "access" = "Drupal\mass_contact\MessageAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\mass_contact\Form\MassContactForm"
 *     },
 *     "list_builder" = "Drupal\mass_contact\MessageListBuilder",
 *     "route_provider" = {
 *       "html" = "\Drupal\mass_contact\Routing\HtmlRouteProvider"
 *     }
 *   },
 *   admin_permission = "mass contact administer",
 *   translatable = FALSE,
 *   base_table = "mass_contact",
 *   data_table = "mass_contact_field_data",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "uid" = "uid",
 *     "label" = "subject"
 *   },
 *   links = {
 *     "add-form": "/mass-contact",
 *     "canonical": "/mass-contact/archive/{mass_contact_message}"
 *   }
 * )
 */
class MassContactMessage extends ContentEntityBase implements MassContactMessageInterface {

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
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
  public function getOwnerId() {
    return $this->getEntityKey('uid');
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
  public function getBody() {
    return $this->get('body')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getCategories() {
    $categories = [];
    foreach ($this->get('categories') as $data) {
      $categories[$data->target_id] = $data->entity;
    }
    return $categories;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormat() {
    return $this->get('body')->format;
  }

  /**
   * {@inheritdoc}
   */
  public function getSentTime() {
    return $this->get('sent')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getSubject() {
    return $this->get('subject')->value;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['categories'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Categories'))
      ->setSetting('target_type', 'mass_contact_category')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_label',
        'weight' => -6,
      ]);

    $fields['subject'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Subject'))
      ->setRequired(TRUE)
      ->setTranslatable(FALSE)
      ->setRevisionable(FALSE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -15,
      ]);

    $fields['body'] = BaseFieldDefinition::create('text_long')
      ->setLabel('body')
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'text_default',
        'weight' => 5,
      ]);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Sent by'))
      ->setDescription(t('The that sent the mass contact message.'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback('Drupal\mass_contact\Entity\MassContactMessage::getCurrentUserId')
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'author',
        'weight' => -7,
      ]);

    $fields['sent'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Sent on'))
      ->setDescription(t('The time that the message was sent.'))
      ->setDefaultValueCallback('Drupal\mass_contact\Entity\MassContactMessage::getDefaultSentTime')
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'timestamp',
        'weight' => -8,
      ]);

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

  /**
   * The default value callback for 'sent' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getDefaultSentTime() {
    return [\Drupal::requestStack()->getCurrentRequest()->server->get('REQUEST_TIME')];
  }

}
