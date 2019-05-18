<?php

namespace Drupal\commerce_migrate_ubercart\Plugin\migrate\source;

use Drupal\migrate\Row;

/**
 * Ubercart product variation source.
 */
trait ProductVariationTrait {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();
    $query->innerJoin('uc_products', 'ucp', 'n.nid = ucp.nid AND n.vid = ucp.vid');
    $query->fields('ucp', ['model', 'sell_price']);
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'vid' => t('The primary identifier for this version.'),
      'log' => $this->t('Revision Log message'),
      'uid' => $this->t('Node UID.'),
      'model' => $this->t('SKU code'),
      'sell_price' => $this->t('Product price'),
    ];
    return parent::fields() + $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Set the currency for each variation based on Ubercart global setting.
    $row->setSourceProperty('currency', $this->getUbercartCurrency());
    return parent::prepareRow($row);
  }

  /**
   * Gets the Ubercart global currency.
   *
   * @return string
   *   The currency.
   */
  public function getUbercartCurrency() {
    static $currency;

    if (empty($currency)) {
      $currency = $this->variableGet('uc_currency_code', 'USD');
    }
    return $currency;
  }

}
