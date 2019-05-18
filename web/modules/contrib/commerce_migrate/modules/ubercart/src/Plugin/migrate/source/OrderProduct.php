<?php

namespace Drupal\commerce_migrate_ubercart\Plugin\migrate\source;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;
use Drupal\migrate\Row;

/**
 * Ubercart order product source.
 *
 * @MigrateSource(
 *   id = "uc_order_product",
 *   source_module = "uc_order",
 * )
 */
class OrderProduct extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('uc_order_products', 'uop')
      ->fields('uop', [
        'order_product_id',
        'order_id',
        'nid',
        'title',
        'qty',
        'price',
        'data',
      ]);
    $query->innerJoin('uc_orders', 'uo', 'uop.order_id = uo.order_id');
    // Ubercart 6 order products have no timestamps to match those on Commerce 2
    // order items, so take them from the order.
    $query->fields('uo', ['created', 'modified']);

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
      'order_product_id' => $this->t('Line item ID'),
      'order_id' => $this->t('Order ID'),
      'nid' => $this->t('Product ID'),
      'title' => $this->t('Product name'),
      'qty' => $this->t('Quantity sold'),
      'price' => $this->t('Price of product sold'),
      'data' => $this->t('Order line item data'),
      'created' => $this->t('Created timestamp, from the order'),
      'modified' => $this->t('Modified timestamp, from the order'),
      'currency' => $this->t("Currency, default to USD'"),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $data = unserialize($row->getSourceProperty('data'));
    // In Ubercart, the module key is set to 'uc_order' so it's removed for
    // D8 Commerce.
    unset($data['module']);
    $row->setSourceProperty('data', $data);

    $row->setSourceProperty('adjustments', $this->getAdjustmentData($row));
    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'order_product_id' => [
        'type' => 'integer',
        'alias' => 'uop',
      ],
    ];
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
      ->condition('uol.order_id', $order_id)
      ->condition('type', 'shipping', '!=');
    $query->innerJoin('uc_orders', 'uo', 'uol.order_id = uo.order_id');
    $adjustments = $query->execute()->fetchAll();

    $currency_code = $row->getSourceProperty('currency');
    foreach ($adjustments as &$adjustment) {
      $adjustment['currency_code'] = $currency_code;
      $adjustment['type'] = 'custom';
    }
    return $adjustments;
  }

}
