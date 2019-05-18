<?php

namespace Drupal\commerce_migrate_ubercart\Plugin\migrate\source;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Provides migration source for orders.
 *
 * @MigrateSource(
 *   id = "uc_order",
 *   source_module = "uc_order"
 * )
 */
class Order extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('uc_orders', 'uo')->fields('uo');
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
    return [
      'order_id' => $this->t('Order ID'),
      'uid' => $this->t('User ID of order'),
      'order_status' => $this->t('Order status'),
      'primary_email' => $this->t('Email associated with order'),
      'host' => $this->t('IP address of customer'),
      'data' => $this->t('Order attributes'),
      'created' => $this->t('Date/time of order creation'),
      'modified' => $this->t('Date/time of last order modification'),
      'order_item_ids' => $this->t('Order item IDs'),
      'refresh_state' => $this->t('Order refresh state'),
      'adjustments' => $this->t('Order adjustments'),
      'comment_id' => $this->t('OrderComments id'),
      'message' => $this->t('Changed timestamp'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Add refresh skip value to the row.
    $row->setSourceProperty('refresh_state', OrderInterface::REFRESH_SKIP);
    $data = unserialize($row->getSourceProperty('data'));
    // Ubercart 6 stores credit card information in a hash. Since this probably
    // isn't necessary so I removed it here.
    unset($data['cc_data']);
    $row->setSourceProperty('data', $data);

    // Puts product order ids for this order in the row.
    $order_id = $row->getSourceProperty('order_id');
    $query = $this->select('uc_order_products', 'uop')
      ->fields('uop', ['order_product_id'])
      ->condition('order_id', $order_id, '=');
    $results = $query->execute()->fetchCol();
    $row->setSourceProperty('order_item_ids', $results);

    // The fields  uc_order_admin_comments, uc_order_comments and order_logs
    // are created by uc_order_field and uc_order_field_instance migrations.
    $value = $this->getFieldValue($order_id, 'uc_order_admin_comments', 'message');
    $row->setSourceProperty('order_admin_comments', $value);
    $value = $this->getFieldValue($order_id, 'uc_order_comments', 'message');
    $row->setSourceProperty('order_comments', $value);
    $value = $this->getFieldValue($order_id, 'uc_order_log', 'changes');
    $row->setSourceProperty('order_logs', $value);

    $row->setSourceProperty('adjustments', $this->getAdjustmentData($row));
    return parent::prepareRow($row);
  }

  /**
   * Retrieves adjustment data for an order.
   *
   * @param \Drupal\migrate\Row $row
   *   The row.
   *
   * @return array
   *   The field values, keyed by delta.
   */
  protected function getAdjustmentData(Row $row) {
    $order_id = $row->getSourceProperty('order_id');
    $query = $this->select('uc_order_line_items', 'uol')
      ->fields('uol')
      ->fields('uo', ['order_id'])
      ->orderBy('weight', 'ASC')
      ->condition('uol.order_id', $order_id);
    $query->innerJoin('uc_orders', 'uo', 'uol.order_id = uo.order_id');
    $adjustments = $query->execute()->fetchAll();

    // Ensure the adjustment has a currency.
    $currency_code = $row->getSourceProperty('currency');
    if (empty($currency_code)) {
      $currency_code = $this->variableGet('uc_currency_code', 'USD');
    }
    foreach ($adjustments as &$adjustment) {
      $adjustment['currency_code'] = $currency_code;
      $adjustment['type'] = 'custom';
    }
    return $adjustments;
  }

  /**
   * Gets data from the source database.
   *
   * @param string $order_id
   *   The order id to get date for.
   * @param string $table
   *   The name of the table.
   * @param string $field_name
   *   The name of the data column.
   *
   * @return array
   *   An array of the rows for this field.
   */
  protected function getFieldValue($order_id, $table, $field_name) {
    $query = $this->select($table, 't')->fields('t')
      ->condition('order_id', $order_id);
    $results = $query->execute()->fetchAll();
    $value = [];
    $i = 0;
    foreach ($results as $result) {
      $value[$i]['value'] = $result[$field_name];
      $value[$i++]['format'] = NULL;
    }
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'order_id' => [
        'type' => 'integer',
        'alias' => 'uo',
      ],
    ];
  }

}
