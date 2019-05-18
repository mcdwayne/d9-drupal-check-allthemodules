<?php

namespace Drupal\email_confirmer\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\email_confirmer\EmailConfirmationInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\email_confirmer\InvalidConfirmationStateException;
use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Url;

/**
 * Defines the email confirmation entity class.
 *
 * @ContentEntityType(
 *   id = "email_confirmer_confirmation",
 *   label = @Translation("Email confirmation"),
 *   label_singular = @Translation("Email confirmation"),
 *   label_plural = @Translation("Email confirmations"),
 *   label_count = @PluralTranslation(
 *     singular = "@count email confirmation",
 *     plural = "@count email confirmations",
 *   ),
 *   handlers = {
 *     "access" = "Drupal\email_confirmer\EmailConfirmationAccessControlHandler",
 *     "form" = {
 *       "response" = "Drupal\email_confirmer\Form\EmailConfirmerResponseForm",
 *     },
 *   },
 *   base_table = "email_confirmer_confirmation",
 *   entity_keys = {
 *     "id" = "cid",
 *     "uuid" = "uuid",
 *     "label" = "email",
 *     "langcode" = "langcode",
 *     "uid" = "uid",
 *   },
 *   admin_permission = "administer email confirmations",
 * )
 */
class EmailConfirmation extends ContentEntityBase implements EmailConfirmationInterface {

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->get('email')->value;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    /** @var \Drupal\Core\Field\BaseFieldDefinition[] $fields */
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['cid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Email confirmation ID'))
      ->setDescription(t('The ID of the email confirmation.'))
      ->setReadOnly(TRUE);

    $fields['email'] = BaseFieldDefinition::create('email')
      ->setLabel(t('Email'))
      ->setDescription(t('The email address of this confirmation.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'type' => 'string',
        'weight' => 0,
      ]);

    $fields['realm'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Realm'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'type' => 'string',
        'weight' => 0,
      ]);

    $fields['ip'] = BaseFieldDefinition::create('string')
      ->setLabel(t('IP address'))
      ->setDescription(t('Confirmation related IP address.'))
      ->setSetting('max_length', 45)
      ->setDefaultValueCallback('Drupal\email_confirmer\Entity\EmailConfirmation::getRequestIp')
      ->setDisplayOptions('view', [
        'type' => 'string',
        'weight' => 0,
      ]);

    $fields['data'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Data'))
      ->setDescription(t('Any arbitrary data to store with the confirmation.'));

    $fields['confirm_url'] = BaseFieldDefinition::create('uri')
      ->setLabel(t('On confirmation URL'))
      ->setDescription(t('A URL to go after an email confirmation was done.'))
      ->setSetting('max_length', 255);

    $fields['cancel_url'] = BaseFieldDefinition::create('uri')
      ->setLabel(t('On cancellation URL'))
      ->setDescription(t('A URL to go after an email confirmation was cancelled.'))
      ->setSetting('max_length', 255);

    $fields['error_url'] = BaseFieldDefinition::create('uri')
      ->setLabel(t('On error URL'))
      ->setDescription(t('A URL to go on email confirmation error.'))
      ->setSetting('max_length', 255);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID who created the email confirmation.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\email_confirmer\Entity\EmailConfirmation::getCurrentUserId')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['private'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Private'))
      ->setDescription(t('A boolean indicating whether the email confirmation is private.'))
      ->setDefaultValue(EmailConfirmationInterface::IS_PUBLIC)
      ->setDisplayOptions('view', [
        'type' => 'string',
        'weight' => 0,
      ]);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Cancelled'))
      ->setDescription(t('A boolean indicating whether the email confirmation is cancelled.'))
      ->setDefaultValue(EmailConfirmationInterface::ACTIVE)
      ->setDisplayOptions('view', [
        'type' => 'string',
        'weight' => 0,
      ]);

    $fields['sent'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Last request sent on'))
      ->setDescription(t('The time the last request was sent.'))
      ->setDisplayOptions('view', [
        'type' => 'timestamp',
        'weight' => 0,
      ]);

    $fields['confirmed'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Confirmed'))
      ->setDescription(t('A boolean indicating whether the email confirmation is confirmed.'))
      ->setDefaultValue(EmailConfirmationInterface::UNCONFIRMED)
      ->setDisplayOptions('view', [
        'type' => 'string',
        'weight' => 0,
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Added on'))
      ->setDefaultValueCallback('Drupal\email_confirmer\Entity\EmailConfirmation::getRequestTime')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'timestamp',
        'weight' => 0,
      ]);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function isPending() {
    return !$this->isExpired()
      && !$this->isCancelled()
      && !$this->isConfirmed();
  }

  /**
   * {@inheritdoc}
   */
  public function isCancelled() {
    return $this->get('status')->value == EmailConfirmationInterface::CANCELLED;
  }

  /**
   * {@inheritdoc}
   */
  public function isConfirmed() {
    return $this->get('confirmed')->value == EmailConfirmationInterface::CONFIRMED;
  }

  /**
   * {@inheritdoc}
   */
  public function isExpired() {
    return REQUEST_TIME > $this->getCreatedTime() + \Drupal::config('email_confirmer.settings')->get('hash_expiration');
  }

  /**
   * {@inheritdoc}
   */
  public function isRequestSent() {
    return !$this->get('sent')->isEmpty();
  }

  /**
   * {@inheritdoc}
   */
  public function isPrivate() {
    return $this->get('private')->value == EmailConfirmationInterface::IS_PRIVATE;
  }

  /**
   * {@inheritdoc}
   */
  public function setPrivate($private = TRUE) {
    $this->get('private')->setValue($private ? EmailConfirmationInterface::IS_PRIVATE : EmailConfirmationInterface::IS_PUBLIC);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus() {
    if ($this->isExpired()) {
      $status = 'expired';
    }
    elseif ($this->isCancelled()) {
      $status = 'cancelled';
    }
    elseif ($this->isConfirmed()) {
      $status = 'confirmed';
    }
    else {
      $status = 'pending';
    }

    return $status;
  }

  /**
   * {@inheritdoc}
   */
  public function sendRequest() {
    $status = $this->getStatus();
    if ($status != 'pending') {
      throw new InvalidConfirmationStateException('Unable to send request email for ' . $status . ' confirmations.');
    }

    // Recently sent?
    if ($this->isRequestSent()
        && $this->getLastRequestDate() + intval(\Drupal::config('email_confirmer.settings')->get('resendrequest_delay')) > REQUEST_TIME) {
      // Add to queue for further processing.
      \Drupal::queue('email_confirmer_requests')->createItem($this->id());
      return TRUE;
    }

    // Send the confirmation request.
    $message = \Drupal::service('plugin.manager.mail')->mail('email_confirmer',
      'confirmation_request',
      (Unicode::substr(PHP_OS, 0, 3) == 'WIN') ? $this->getEmail() : '"' . addslashes(Unicode::mimeHeaderEncode(\Drupal::config('system.site')->get('name'))) . '" <' . $this->getEmail() . '>',
      $this->language(),
      ['context' => ['email_confirmer_confirmation' => $this]]);

    if ($ok = !empty($message['result'])) {
      $this->setLastRequestDate(REQUEST_TIME);
    }

    return $ok;
  }

  /**
   * {@inheritdoc}
   */
  public function confirm($hash) {
    $status = $this->getStatus();
    if ($status != 'pending') {
      throw new InvalidConfirmationStateException('Unable to confirm ' . $status . ' confirmations.');
    }

    if ($hash == $this->getHash()) {
      $this->get('confirmed')->setValue(EmailConfirmationInterface::CONFIRMED);
      // Invoke email_confirmer hook.
      \Drupal::moduleHandler()->invokeAll('email_confirmer', ['confirm', $this]);
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function cancel() {
    $status = $this->getStatus();
    if ($status != 'pending') {
      throw new InvalidConfirmationStateException('Unable to cancel ' . $status . ' confirmations.');
    }

    $this->get('status')->setValue(EmailConfirmationInterface::CANCELLED);
    // Invoke email_confirmer hook.
    \Drupal::moduleHandler()->invokeAll('email_confirmer', ['cancel', $this]);
  }

  /**
   * {@inheritdoc}
   */
  public function getHash() {
    $data = $this->getEmail()
      . $this->getCreatedTime()
      . $this->getIp() ?: '';
    return Crypt::hmacBase64($data, \Drupal::service('private_key')->get());
  }

  /**
   * {@inheritdoc}
   */
  public function getEmail() {
    return $this->get('email')->getString();
  }

  /**
   * {@inheritdoc}
   */
  public function setEmail($email) {
    $this->get('email')->setValue($email);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRealm() {
    return $this->get('realm')->getString();
  }

  /**
   * {@inheritdoc}
   */
  public function setRealm($realm) {
    $this->get('realm')->setValue($realm);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getIp() {
    return $this->get('ip')->isEmpty() ? FALSE : $this->get('ip')->getString();
  }

  /**
   * {@inheritdoc}
   */
  public function setIp($ip) {
    if (ip2long($ip) === FALSE) {
      throw new \InvalidArgumentException($ip . ' is not a valid IP address.');
    }
    $this->get('ip')->setValue($ip);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getProperty($key = NULL) {
    $data_field = $this->get('data');
    $values = $data_field->isEmpty() ? [] : $data_field->first()->toArray();
    if ($key) {
      return isset($values[$key]) ? $values[$key] : NULL;
    }
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function setProperty($key, $value = NULL) {
    $item = $this->get('data')->isEmpty() ? $this->get('data')->appendItem() : $this->get('data')->first();
    $map = $item->getValue();
    if ($value === NULL && isset($map[$key])) {
      unset($map[$key]);
    }
    else {
      $map[$key] = $value;
    }
    $item->setValue($map);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getProperties() {
    $map = [];
    if (!$this->get('data')->isEmpty()) {
      $map = $this->get('data')->first()->getValue();
    }
    return $map;
  }

  /**
   * {@inheritdoc}
   */
  public function getLastRequestDate() {
    return $this->get('sent')->isEmpty() ? FALSE : intval($this->get('sent')->getString());
  }

  /**
   * {@inheritdoc}
   */
  public function setLastRequestDate($timestamp) {
    $this->get('sent')->setValue($timestamp);
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
    $this->get('created')->setValue($timestamp);
    return $this;
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
   * Default value callback for 'ip' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getRequestIp() {
    return [\Drupal::request()->getClientIp()];
  }

  /**
   * Default value callback for 'created' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getRequestTime() {
    return [REQUEST_TIME];
  }

  /**
   * {@inheritdoc}
   */
  public function setResponseUrl(Url $url, $operation = NULL) {
    $operations = $operation ? [$operation] : ['confirm', 'cancel', 'error'];
    foreach ($operations as $operation) {
      $this->get($operation . '_url')->setValue($url->toUriString());
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getResponseUrl($operation) {
    $uri = $this->get($operation . '_url')->getString();
    return $uri ? Url::fromUri($uri) : NULL;
  }

}
