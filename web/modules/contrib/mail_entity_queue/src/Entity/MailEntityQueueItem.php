<?php

namespace Drupal\mail_entity_queue\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines the mail queue item entity class.
 *
 * @ContentEntityType(
 *   id = "mail_entity_queue_item",
 *   label = @Translation("Mail entity queue item"),
 *   label_singular = @Translation("Mail entity queue item"),
 *   label_plural = @Translation("Mail entity queue items"),
 *   label_count = @PluralTranslation(
 *     singular = "@count mail entity queue item",
 *     plural = "@count mail entity queue items",
 *   ),
 *   bundle_label = @Translation("Mail entity queue"),
 *   handlers = {
 *     "event" = "Drupal\mail_entity_queue\Event\MailEntityQueueItemEvent",
 *     "storage" = "Drupal\Core\Entity\Sql\SqlContentEntityStorage",
 *     "access" = "Drupal\mail_entity_queue\MailEntityQueueItemAccessControlHandler",
 *     "list_builder" = "Drupal\mail_entity_queue\MailEntityQueueItemListBuilder",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "edit" = "Drupal\mail_entity_queue\Form\MailEntityQueueItemForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "process" = "Drupal\mail_entity_queue\Form\MailEntityQueueItemProcessForm",
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "mail_entity_queue_item",
 *   data_table = "mail_entity_queue_item_field_data",
 *   admin_permission = "administer mail entity queue items",
 *   fieldable = TRUE,
 *   field_ui_base_route = "entity.mail_entity_queue.edit_form",
 *   translatable = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "queue",
 *     "label" = "id",
 *     "mail" = "mail",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "edit-form" = "/admin/structure/mail-entity-queue/{mail_entity_queue_item}",
 *     "delete-form" = "/admin/structure/mail-entity-queue/{mail_entity_queue_item}/delete",
 *     "process-form" = "/admin/structure/mail-entity-queue/{mail_entity_queue_item}/process",
 *     "collection" = "/admin/structure/mail-entity-queue",
 *   },
 *   bundle_entity_type = "mail_entity_queue",
 * )
 */
class MailEntityQueueItem extends ContentEntityBase implements MailEntityQueueItemInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['mail'] = BaseFieldDefinition::create('email')
      ->setLabel(new TranslatableMarkup('Mail'))
      ->setDescription(new TranslatableMarkup('The address the message will be sent to.'))
      ->setReadOnly(FALSE)
      ->setRequired(TRUE);

    $fields['data'] = BaseFieldDefinition::create('map')
      ->setLabel(new TranslatableMarkup('Data'))
      ->setDescription(new TranslatableMarkup('Data array to compose the mail.'))
      ->setReadOnly(TRUE)
      ->setRequired(TRUE);

    $fields['entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Entity type'))
      ->setDescription(new TranslatableMarkup('The type of the entity to which this item is related.'))
      ->setSetting('is_ascii', TRUE)
      ->setSetting('max_length', EntityTypeInterface::ID_MAX_LENGTH)
      ->setReadOnly(TRUE)
      ->setDefaultValue(NULL);

    $fields['entity_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Entity ID'))
      ->setDescription(new TranslatableMarkup('The ID of the entity to which this item is related.'))
      ->setSetting('unsigned', TRUE)
      ->setReadOnly(TRUE)
      ->setDefaultValue(NULL);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Created'))
      ->setDescription(new TranslatableMarkup('The time this item was added to the queue.'))
      ->setReadOnly(TRUE)
      ->setRequired(TRUE);

    $fields['changed'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(new TranslatableMarkup('Changed'))
      ->setDescription(new TranslatableMarkup('The time this item was last changed.'))
      ->setReadOnly(TRUE)
      ->setRequired(TRUE)
      ->setDefaultValue(NULL);

    $fields['attempts'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Attempts'))
      ->setDescription(new TranslatableMarkup('The number of attempts to send this item.'))
      ->setSetting('unsigned', TRUE)
      ->setReadOnly(TRUE)
      ->setRequired(TRUE)
      ->setDefaultValue(0);

    $fields['status'] = BaseFieldDefinition::create('list_integer')
      ->setLabel(t('Status'))
      ->setDescription(new TranslatableMarkup('Processing status of the queue item.'))
      ->setDefaultValue(self::PENDING)
      ->setSetting('unsigned', TRUE)
      ->setRequired(TRUE)
      ->setSetting('allowed_values', self::getStatusOptions())
      ->setDisplayOptions('form', [
        'type' => 'options_select'
      ])
      ->setDisplayConfigurable('form', TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function getStatusOptions() {
    return [
      self::PENDING => t('Pending'),
      self::SENT => t('Sent'),
      self::RETRYING => t('Retrying'),
      self::DISCARDED => t('Discarded')
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getAttempts() {
    return $this->get('attempts')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setAttempts($attempts) {
    $this->set('attempts', $attempts);

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
  public function getData() {
    return $this->get('data')->first()->getValue();
  }

  /**
   * {@inheritdoc}
   */
  public function setData($data) {
    $this->set('data', $data);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMail() {
    return $this->get('mail')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setMail($mail) {
    $this->set('mail', $mail);
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceEntityId() {
    return $this->get('entity_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSourceEntityId($entity_id) {
    $this->set('entity_id', $entity_id);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceEntityType() {
    return $this->get('entity_type')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSourceEntityType($entity_type) {
    $this->set('entity_type', $entity_type);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function queue() {
    return MailEntityQueue::load($this->bundle());
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus() {
    return $this->get('status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus($code) {
    $this->set('status', $code);
  }

}
