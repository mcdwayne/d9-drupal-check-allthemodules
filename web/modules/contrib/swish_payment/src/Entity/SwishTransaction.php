<?php

namespace Drupal\swish_payment\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Swish transaction entity.
 *
 * @ingroup swish_payment
 *
 * @ContentEntityType(
 *   id = "swish_transaction",
 *   label = @Translation("Swish transaction"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\swish_payment\SwishTransactionListBuilder",
 *     "views_data" = "Drupal\swish_payment\Entity\SwishTransactionViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\swish_payment\Form\SwishTransactionForm",
 *       "add" = "Drupal\swish_payment\Form\SwishTransactionForm",
 *       "edit" = "Drupal\swish_payment\Form\SwishTransactionForm",
 *       "delete" = "Drupal\swish_payment\Form\SwishTransactionDeleteForm",
 *     },
 *     "access" = "Drupal\swish_payment\SwishTransactionAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\swish_payment\SwishTransactionHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "swish_transaction",
 *   admin_permission = "administer swish transaction entities",
 *   entity_keys = {
 *     "id" = "transaction_id",
 *     "label" = "transaction_id",
 *     "uid" = "user_id",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/swish_transaction/{swish_transaction}",
 *     "add-form" = "/admin/structure/swish_transaction/add",
 *     "edit-form" = "/admin/structure/swish_transaction/{swish_transaction}/edit",
 *     "delete-form" = "/admin/structure/swish_transaction/{swish_transaction}/delete",
 *     "collection" = "/admin/structure/swish_transaction",
 *   },
 *   field_ui_base_route = "swish_transaction.settings"
 * )
 */
class SwishTransaction extends ContentEntityBase implements SwishTransactionInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += array(
      'user_id' => \Drupal::currentUser()->id(),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getTransactionId() {
    return $this->get('transaction_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTransactionId($transaction_id) {
    $this->set('transaction_id', $transaction_id);
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
  public function getChangedTime() {
    return $this->get('changed')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setChangedTime($timestamp) {
    $this->set('changed', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPaidTime() {
    return $this->get('paid')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setPaidTime($timestamp) {
    $this->set('paid', $timestamp);
    return $this;
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
  public function getPayeePaymentReference() {
    return $this->get('payee_payment_reference')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setPayeePaymentReference($value) {
    $this->set('payee_payment_reference', $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentReference() {
    return $this->get('payment_reference')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setPaymentReference($value) {
    $this->set('payment_reference', $value);
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
  public function setMessage($value) {
    $this->set('message', $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAmount() {
    return $this->get('amount')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setAmount($value) {
    $this->set('amount', $value);
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
  public function setStatus($value) {
    $this->set('status', $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPayerAlias() {
    return $this->get('payer_alias')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setPayerAlias($value) {
    $this->set('payer_alias', $value);
    return $this;
  }


  /**
   * {@inheritdoc}
   */
  public function getErrorCode() {
    return $this->get('error_code')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setErrorCode($value) {
    $this->set('error_code', $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getErrorMessage() {
    return $this->get('error_message')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setErrorMessage($value) {
    $this->set('error_message', $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAdditionalInformation() {
    return $this->get('additional_information')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setAdditionalInformation($value) {
    $this->set('additional_information', $value);
    return $this;
  }

  /**
   * @return JSON representation of the SwishTransaction.
   */
  public function toArray() {
    $arr = parent::toArray();

    $arr['transaction_id'] = $this->getTransactionId();
    $arr['additional_information'] = $this->getAdditionalInformation();
    $arr['error_message'] = $this->getErrorMessage();
    $arr['error_code'] = $this->getErrorCode();
    $arr['payee_payment_reference'] = $this->getPayeePaymentReference();
    $arr['payment_reference'] = $this->getPaymentReference();
    $arr['payer_alias'] = $this->getPayerAlias();
    $arr['status'] = $this->getStatus();
    $arr['amount'] = $this->getAmount();
    $arr['message'] = $this->getMessage();
    $arr['user_id'] = $this->getOwnerId();
    $arr['paid'] = $this->getPaidTime();
    $arr['created'] = $this->getCreatedTime();
    $arr['changed'] = $this->getChangedTime();

    return $arr;
  }



  /**
   * {@inheritdoc}
   */
//  public function isPublished() {
//    return (bool) $this->getEntityKey('status');
//  }

  /**
   * {@inheritdoc}
   */
//  public function setPublished($published) {
//    $this->set('status', $published ? TRUE : FALSE);
//    return $this;
//  }

  /**
   * {@inheritdoc}
   */
  public static function loadByTransactionId($transaction_id) {

  }


  /**
   * {@inheritdoc}
   */

  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Swish transaction entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => array(
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ),
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['transaction_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Translation ID'))
      ->setDescription(t('The ID of the Swish transaction entity.'))
      ->setSettings(array(
        'max_length' => 50,
        'text_processing' => 0,
      ))
      ->setDefaultValue('')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['payee_payment_reference'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Payee Payment Reference'))
      ->setDescription(t('This reference could be order id or similar.'))
      ->setSettings(array(
        'max_length' => 50,
        'text_processing' => 0,
      ))
      ->setDefaultValue('')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['payment_reference'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Payment Reference'))
      ->setDescription(t('Payment reference, from the bank, of the payment that occurred based on the Payment request. '))
      ->setSettings(array(
        'max_length' => 50,
        'text_processing' => 0,
      ))
      ->setDefaultValue('')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['payer_alias'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Payer Alias'))
      ->setDescription(t('The registered Cell phone number of the person that makes the payment. '))
      ->setSettings(array(
        'min_length' => 8,
        'max_length' => 15,
        'text_processing' => 0,
      ))
      ->setDefaultValue('')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['message'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Message'))
      ->setDescription(t('Merchant supplied message about the payment/order.'))
      ->setSettings(array(
        'max_length' => 50,
        'text_processing' => 0,
      ))
      ->setDefaultValue('')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);


    $fields['amount'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Amount'))
      ->setDescription(t('The amount in SEK'))
      ->setSettings(array(
        'min' => 1,
        'max' => 99999999.99,
        'size' => "10,2",
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'numeric',
        'weight' => -4,
        ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -4,
        ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Status'))
      ->setDescription(t('The payment status'))
      ->setSettings(array(
        'max_length' => 8,
        'text_processing' => 0,
      ))
      ->setDefaultValue(null)
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);


    $fields['paid'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Date Paid'))
      ->setDescription(t('The time the payment was done.'));

    $fields['error_code'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Error Code'))
      ->setDescription(t('A code indicating what type of error occurred.'))
      ->setSettings(array(
        'max_length' => 16,
        'text_processing' => 0,
      ))
      ->setDefaultValue(null)
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['error_message'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Error Message'))
      ->setDescription(t('A descriptive error message.'))
      ->setSettings(array(
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDefaultValue(null)
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['additional_information'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Additional information'))
      ->setDescription(t('Additional information about the error.'))
      ->setSettings(array(
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDefaultValue(null)
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

/*    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Swish transaction is published.'))
      ->setDefaultValue(TRUE);*/

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
