<?php

namespace Drupal\commerce_migrate_commerce\Plugin\migrate\source\commerce1;

use CommerceGuys\Intl\Currency\CurrencyRepository;
use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Gets the flat rate shipping service.
 *
 * @MigrateSource(
 *   id = "commerce1_shipping_flat_rate",
 *   source_module = "commerce_order"
 * )
 */
class ShippingFlatRate extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('commerce_flat_rate_service', 'fr')
      ->fields('fr');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'name' => t('Flat rate service name'),
      'title' => t('Flat rate title'),
      'display_title' => t('Flat rate display title'),
      'description' => t('Description'),
      'rules_component' => t('Rules component'),
      'amount' => t('Amount'),
      'number' => t('The amount converted to a Commerce price amount'),
      'currency_code' => t('Currency code'),
      'data' => t('Shipping data'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $row->setSourceProperty('data', unserialize($row->getSourceProperty('data')));

    // Include the number of currency fraction digits in the rate.
    $currencyRepository = new CurrencyRepository();
    $currency_code = $row->getSourceProperty('currency_code');
    $fraction_digits = $currencyRepository->get($currency_code)->getFractionDigits();
    $number = bcdiv($row->getSourceProperty('amount'), bcpow(10, $fraction_digits), $fraction_digits);
    $row->setSourceProperty('number', $number);
    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['name']['type'] = 'string';
    return $ids;
  }

}
