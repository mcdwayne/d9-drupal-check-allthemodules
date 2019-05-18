<?php

namespace Drupal\commerce_refund_log\Entity;

use Drupal\commerce_price\Price;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the refund log entry.
 *
 * @ContentEntityType(
 *   id = "commerce_refund_log_entry",
 *   label = @Translation("Refund log entry"),
 *   label_collection = @Translation("Refund log entries"),
 *   label_singular = @Translation("refund log entry"),
 *   label_plural = @Translation("refund log entries"),
 *   label_count = @PluralTranslation(
 *     singular = "@count refund log entry",
 *     plural = "@count refund log entries",
 *   ),
 *   handlers = {
 *     "access" = "Drupal\commerce_refund_log\RefundLogEntryAccessControlHandler",
 *     "list_builder" = "Drupal\commerce_refund_log\RefundLogEntryListBuilder",
 *     "storage" = "Drupal\commerce_refund_log\RefundLogEntryStorage",
 *     "views_data" = "Drupal\commerce\CommerceEntityViewsData",
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "commerce_refund_log_entry",
 *   admin_permission = "administer commerce_refund_log_entry",
 *   entity_keys = {
 *     "id" = "refund_log_entry_id",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "collection" = "/admin/commerce/orders/{commerce_order}/payments/{commerce_payment}/refunds"
 *   },
 * )
 */
class RefundLogEntry extends ContentEntityBase implements RefundLogEntryInterface {

  /**
   * {@inheritdoc}
   */
  public function label() {
    // UIs should use the number formatter to show a more user-readable version.
    return $this->getAmount()->__toString();
  }

  /**
   * {@inheritdoc}
   */
  public function getPayment() {
    return $this->get('payment_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentId() {
    return $this->get('payment_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteId() {
    return $this->get('remote_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setRemoteId($remote_id) {
    $this->set('remote_id', $remote_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteState() {
    return $this->get('remote_state')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setRemoteState($remote_state) {
    $this->set('remote_state', $remote_state);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAmount() {
    return $this->get('amount')->first()->toPrice();
  }

  /**
   * {@inheritdoc}
   */
  public function setAmount(Price $amount) {
    $this->set('amount', $amount);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRefundTime() {
    return $this->get('refund_time')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setRefundTime($timestamp) {
    $this->set('refund_time', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['payment_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Payment'))
      ->setDescription(t('The parent payment.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'commerce_payment')
      ->setReadOnly(TRUE);

    $fields['remote_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Remote ID'))
      ->setDescription(t('The remote payment ID.'))
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('view', TRUE);

    $fields['remote_state'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Remote State'))
      ->setDescription(t('The remote payment state.'))
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('view', TRUE);

    $fields['amount'] = BaseFieldDefinition::create('commerce_price')
      ->setLabel(t('Amount'))
      ->setDescription(t('The refund amount.'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['refund_time'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Refund time'))
      ->setDescription(t('The time when the refund happened.'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
