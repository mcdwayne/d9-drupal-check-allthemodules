<?php

namespace Drupal\sms_ui\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\sms\Entity\SmsMessage;
use Drupal\sms\Entity\SmsMessageInterface as EntitySmsMessageInterface;
use Drupal\sms\Message\SmsMessageInterface;

/**
 * Encapsulates information on the history of a particular SMS message.
 *
 * This class allows chunked and split messages to be tracked and rendered
 * together as a unit or group.
 *
 * @ContentEntityType(
 *   id = "sms_history",
 *   label = @Translation("SMS History"),
 *   base_table = "sms_history",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   handlers = {
 *     "list_builder" = "Drupal\sms_ui\SmsHistoryListBuilder",
 *   },
 * )
 */
class SmsHistory extends ContentEntityBase implements SmsHistoryInterface {

  /**
   * {@inheritdoc}
   */
  public function setStatus($status) {
    $this->set('status', $status);
    return $this;
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
  public function setExpiry($value) {
    $this->set('expiry', $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getExpiry() {
    return $this->get('expiry')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSmsMessages(array $sms_messages) {
    $this->messages->filter(function ($item) { return false; });
    $message = NULL;
    foreach ($sms_messages as $sms_message) {
      $message = SmsMessage::convertFromSmsMessage($sms_message);
      $this->messages->appendItem($message);
    }
    // Set the history owner to be the owner of the SMS message.
    if ($message) {
      $this->set('owner', $message->getSenderEntity());
    }
    else {
      $this->set('owner', NULL);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSmsMessages() {
    $sms_messages = [];
    foreach ($this->get('messages') as $sms_message) {
      if ($sms_message->entity !== NULL) {
        $sms_messages[] = $sms_message->entity;
      }
    }
    return $sms_messages;
  }

  /**
   * {@inheritdoc}
   */
  public function addSmsMessage(SmsMessageInterface $sms_message) {
    $message = SmsMessage::convertFromSmsMessage($sms_message);
    $this->messages->appendItem($message);
    if (!$this->getOwner()) {
      $this->set('owner', $message->getSenderEntity());
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteSmsMessages() {
    foreach ($this->get('messages') as $sms_message) {
      $sms_message->entity && $sms_message->entity->delete();
    }
    $this->messages->filter(function ($item) { return false; });
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage() {
    if (isset($this->get('messages')->first()->entity)) {
      return $this->get('messages')->first()->entity->getMessage();
    }
    else {
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getResults() {
    $results = [];
    foreach ($this->get('messages') as $sms_message) {
      if (isset($sms_message->entity) && $sms_message->entity->getResult()) {
        $results[] = $sms_message->entity->getResult();
      }
    }
    return $results;
  }

  public function getReports() {
    $reports = [];
    foreach ($this->get('messages') as $sms_message) {
      if (isset($sms_message->entity) && $sms_message->entity->getResult()) {
        $reports += $sms_message->entity->getReports();
      }
    }
    return $reports;
  }

  /**
   * {@inheritdoc}
   */
  public function getRecipients() {
    $recipients = [];
    foreach ($this->get('messages') as $sms_message) {
      if (isset($sms_message->entity)) {
        $recipients = array_merge($recipients, $sms_message->entity->getRecipients());
      }
    }
    return $recipients;
  }

  /**
   * {@inheritdoc}
   */
  public function getSender() {
    if (isset($this->get('messages')->first()->entity)) {
      return $this->get('messages')->first()->entity->getSender();
    }
    else {
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('owner')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // @todo Do we need to add an owner field here???
    $fields['messages'] = BaseFieldDefinition::create('entity_reference')
      ->setSetting('target_type', 'sms')
      ->setLabel(t('SMS Messages'))
      ->setDescription(t('The SMS messages contained in this history item.'))
      ->setReadOnly(TRUE)
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setRequired(TRUE);

    $fields['owner'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Message owner'))
      ->setDescription(t('The SMS message history owner.'))
      ->setSetting('target_type', 'user')
      ->setReadOnly(TRUE)
      ->setRequired(TRUE);

    $fields['status'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Message status'))
      ->setDescription(t('Whether this message is a draft or sent message'))
      ->setRequired(TRUE);

    $fields['expiry'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Expiry date'))
      ->setDescription(t('The time when this history item should be deleted.'))
      ->setRequired(TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);
    foreach ($entities as $sms_history) {
      $sms_history->deleteSmsMessages();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getHistoryForMessage(EntitySmsMessageInterface $sms_message) {
    $query = \Drupal::entityTypeManager()->getStorage('sms_history')->getQuery();
    $ids = $query
      ->condition('messages.target_id', $sms_message->id())
      ->execute();
    return $ids ? SmsHistory::load(reset($ids)) : NULL;
  }

}
