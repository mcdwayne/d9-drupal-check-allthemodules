<?php

namespace Drupal\commerce_migrate_commerce\Plugin\migrate\source\commerce1;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\d7\FieldableEntity;

/**
 * Gets Commerce 1 payment transactions from database.
 *
 * @MigrateSource(
 *   id = "commerce1_payment_transaction",
 *   source_module = "commerce_payment"
 * )
 */
class PaymentTransaction extends FieldableEntity {

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'transaction_id' => $this->t('Transaction ID'),
      'revision_id' => $this->t('Revision ID'),
      'uid' => $this->t('User ID'),
      'order_id' => $this->t('Order ID'),
      'payment_method' => $this->t('Payment Method'),
      'instance_id' => $this->t('Instance ID'),
      'remote_id' => $this->t('Remote ID'),
      'message' => $this->t('Message'),
      'message_variables' => $this->t('Message Variables'),
      'amount' => $this->t('Amount'),
      'currency_code' => $this->t('Currency Code'),
      'status' => $this->t('Status'),
      'remote_status' => $this->t('Remote Status'),
      'payload' => $this->t('Payload'),
      'created' => $this->t('Created'),
      'changed' => $this->t('Changed'),
      'data' => $this->t('Data'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['transaction_id']['type'] = 'integer';
    $ids['transaction_id']['alias'] = 'pt';
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Process the receipts in chronological order.
    return $this->select('commerce_payment_transaction', 'pt')
      ->fields('pt')
      ->orderBy('changed');
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $nid = $row->getSourceProperty('transaction_id');
    $vid = $row->getSourceProperty('revision_id');
    foreach (array_keys($this->getFields('commerce_payment_transaction')) as $field) {
      $row->setSourceProperty($field, $this->getFieldValues('commerce_payment_transaction', $field, $nid, $vid));
    }
    return parent::prepareRow($row);
  }

}
