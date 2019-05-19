<?php

namespace Drupal\ubercart_funds\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Transaction entity.
 *
 * @ingroup ubercart_funds
 *
 * @ContentEntityType(
 *   id = "ubercart_funds_transaction",
 *   label = @Translation("Transaction"),
 *   label_collection = @Translation("Transactions"),
 *   label_singular = @Translation("transaction"),
 *   label_plural = @Translation("transactions"),
 *   label_count = @PluralTranslation(
 *     singular = "@count transaction",
 *     plural = "@count transactions",
 *   ),
 *   bundle_label = @Translation("Transaction type"),
 *   handlers = {
 *     "event" = "Drupal\ubercart_funds\Event\TransactionEvent",
 *     "views_data" = "Drupal\ubercart_funds\FundsEntityViewsData",
 *   },
 *   base_table = "uc_funds_transactions",
 *   admin_permission = "administer transactions",
 *   translatable = FALSE,
 *   entity_keys = {
 *     "id" = "transaction_id",
 *     "bundle" = "type",
 *     "label" = "brut_amount",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *     "uid" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/ubercart/funds/view-transactions",
 *   },
 *   bundle_entity_type = "ubercart_funds_transaction_type",
 *   fieldable = FALSE,
 * )
 */
class Transaction extends ContentEntityBase implements TransactionInterface {

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('owner')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('owner', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->getEntityKey('owner');
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('owner', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getIssuer() {
    return $this->get('issuer')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setIssuer(UserInterface $account) {
    $this->set('issuer', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getIssuerId() {
    return $this->get('issuer')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setIssuerId($uid) {
    $this->set('issuer', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRecipient() {
    return $this->get('recipient')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setRecipient(UserInterface $account) {
    $this->set('recipient', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRecipientId() {
    return $this->get('recipient')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setRecipientId($uid) {
    $this->set('recipient', $uid);
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
  public function getMethod() {
    return $this->get('method')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setMethod($method) {
    $this->set('method', $method);
    return $this;
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
  public function getBrutAmount() {
    return $this->get('brut_amount')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setBrutAmount($brut_amount) {
    $this->set('brut_amount', $brut_amount);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getNetAmount() {
    return $this->get('net_amount')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setNetAmount($net_amount) {
    $this->set('net_amount', $net_amount);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFee() {
    return $this->get('fee')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setFee($fee) {
    $this->set('fee', $fee);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrencyCode() {
    return $this->get('currency')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCurrencyCode($currency_code) {
    $this->set('currency', $currency_code);
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
  public function setStatus($status) {
    $this->set('status', $status);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getNotes() {
    return $this->get('notes')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setNotes($notes) {
    $this->set('notes', $notes);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['issuer'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Issuer'))
      ->setDescription(t('The issuer id of the transaction.'))
      ->setRequired(TRUE)
      ->setDefaultValueCallback('Drupal\ubercart_funds\Entity\Transaction::getCurrentUserId')
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE)
      ->setSetting('target_type', 'user');

    $fields['recipient'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Recipient'))
      ->setDescription(t('The recipient id of the transaction.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'user')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 2,
      ]);

    $fields['method'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Payment method'))
      ->setDescription(t('The transaction payment method.'))
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('When the transaction has been initiated.'))
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 50,
      ]);

    $fields['brut_amount'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Brut amount'))
      ->setDescription(t('The amount of the transaction before applying the fees.'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'decimal',
        'weight' => 1,
      ])
      ->setSetting('display_description', TRUE);

    $fields['net_amount'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Net amount'))
      ->setDescription(t('The total amount of the transaction.'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', FALSE);

    $fields['fee'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Fee'))
      ->setDescription(t('Fee applied to the transaction.'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', FALSE);

    $fields['currency'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Currency'))
      ->setDescription(t('The currency of the transaction.'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['status'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Status'))
      ->setDescription(t('The current status of the transaction.'))
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['notes'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Notes'))
      ->setDescription(t('Notes of the issuer of the transaction.'))
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    return $fields;
  }

  /**
   * Default value callback for 'issuer' base field definition.
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
