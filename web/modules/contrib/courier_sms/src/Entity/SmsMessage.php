<?php

namespace Drupal\courier_sms\Entity;

use Drupal\courier\ChannelBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\courier_sms\SmsMessageInterface;
use Drupal\sms\Entity\SmsMessage as SmsMessageEntity;
use Drupal\sms\Entity\SmsMessageInterface as SmsMessageEntityInterface;

/**
 * Defines storage for a SMS.
 *
 * @ContentEntityType(
 *   id = "courier_sms",
 *   label = @Translation("SMS"),
 *   handlers = {
 *     "form" = {
 *       "default" = "Drupal\courier_sms\Form\SmsMessage",
 *       "add" = "Drupal\courier_sms\Form\SmsMessage",
 *       "edit" = "Drupal\courier_sms\Form\SmsMessage",
 *       "delete" = "Drupal\courier_sms\Form\SmsMessage",
 *     },
 *   },
 *   base_table = "courier_sms",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/courier_sms/{sms}/edit",
 *     "edit-form" = "/courier_sms/{sms}/edit",
 *     "delete-form" = "/courier_sms/{sms}/delete",
 *   }
 * )
 */
class SmsMessage extends ChannelBase implements SmsMessageInterface {

  /**
   * {@inheritdoc}
   */
  public function getRecipient() {
    return $this->get('recipient')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setRecipient($recipient) {
    $this->set('recipient', $recipient);
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
    $this->set('message', ['value' => $message]);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  static public function sendMessages(array $messages, $options = []) {
    /** @var \Drupal\sms\Provider\SmsProviderInterface $sms_service */
    $sms_service = \Drupal::service('sms_provider');

    /** @var static[] $messages */
    foreach ($messages as $message) {
      $sms_message = SmsMessageEntity::create();
      $sms_message
        ->setMessage($message->getMessage())
        ->setDirection(SmsMessageEntityInterface::DIRECTION_OUTGOING)
        ->addRecipient($message->getRecipient());
      $sms_service->queue($sms_message);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function applyTokens() {
    $tokens = $this->getTokenValues();
    $this->setMessage(\Drupal::token()->replace($this->getMessage(), $tokens));
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  function isEmpty() {
    return empty($this->getMessage());
  }

  /**
   * {@inheritdoc}
   */
  public function importTemplate($content) {
    $this->setMessage($content['message']);
  }

  /**
   * {@inheritdoc}
   */
  public function exportTemplate() {
    return [
      'message' => $this->getMessage(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('SMS ID'))
      ->setDescription(t('The SMS ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The SMS message UUID.'))
      ->setReadOnly(TRUE);

    $fields['recipient'] = BaseFieldDefinition::create('telephone')
      ->setLabel(t('Recipient'))
      ->setDescription(t('Recipient phone number.'))
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'hidden',
      ]);

    $fields['message'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Message'))
      ->setDescription(t('The SMS message.'))
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 50,
        'settings' => array(
          'rows' => 2,
        ),
      ]);

    return $fields;
  }

}
