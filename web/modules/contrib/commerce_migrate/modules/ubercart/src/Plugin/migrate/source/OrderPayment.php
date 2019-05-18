<?php

namespace Drupal\commerce_migrate_ubercart\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Provides migration source for orders.
 *
 * @MigrateSource(
 *   id = "uc_payment_receipt",
 *   source_module = "uc_payment"
 * )
 */
class OrderPayment extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('uc_payment_receipts', 'upr')->fields('upr');
    $query->innerJoin('uc_orders', 'uo', 'upr.order_id = uo.order_id');
    $query->orderBy('received');
    $query->orderBy('receipt_id');
    /** @var \Drupal\Core\Database\Schema $db */
    if ($this->getDatabase()->schema()->fieldExists('uc_orders', 'currency')) {
      // Currency column is in the source.
      $query->addField('uo', 'currency');
    }
    else {
      // If the currency column does not exist, add it as an expression to
      // normalize the query results.
      $currency_code = $this->variableGet('uc_currency_code', 'USD');
      $query->addExpression(':currency_code', 'currency', [':currency_code' => $currency_code]);
    }
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'receipt_id' => $this->t('Payment receipt ID'),
      'order_id' => $this->t('Order ID'),
      'method' => $this->t('Payment method'),
      'amount' => $this->t('Payment amount'),
      'currency' => $this->t('Currency'),
      'refund_amount' => $this->t('Refunded amount'),
      'uid' => $this->t('User ID of order'),
      'data' => $this->t('Payment data'),
      'received' => $this->t('Date/time of payment was received'),
      'state' => $this->t('State of the order'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $row->setSourceProperty('data', unserialize($row->getSourceProperty('data')));
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'receipt_id' => [
        'type' => 'integer',
        'alias' => 'upr',
      ],
    ];
  }

}
