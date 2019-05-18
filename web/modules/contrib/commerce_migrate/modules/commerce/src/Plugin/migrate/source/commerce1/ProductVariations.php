<?php

namespace Drupal\commerce_migrate_commerce\Plugin\migrate\source\commerce1;

use CommerceGuys\Intl\Currency\CurrencyRepository;
use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\d7\FieldableEntity;

/**
 * Gets Commerce 1 commerce_product data from database.
 *
 * @MigrateSource(
 *   id = "commerce1_product",
 *   source_module = "commerce_product"
 * )
 */
class ProductVariations extends FieldableEntity {

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'product_id' => t('Product variation ID'),
      'sku' => t('SKU'),
      'title' => t('Title'),
      'type' => t('Type'),
      'language' => t('Language'),
      'status' => t('Status'),
      'created' => t('Created'),
      'changed' => t('Changes'),
      'data' => t('Data'),
      'commerce_price' => t('Price with amount, currency_code and fraction_digits'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['product_id']['type'] = 'integer';
    $ids['product_id']['alias'] = 'p';
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('commerce_product', 'p')->fields('p');
    if (isset($this->configuration['product_variation_type'])) {
      $query->condition('p.type', $this->configuration['product_variation_type']);
    }
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $product_id = $row->getSourceProperty('product_id');
    $revision_id = $row->getSourceProperty('revision_id');
    foreach (array_keys($this->getFields('commerce_product', $row->getSourceProperty('type'))) as $field) {
      $row->setSourceProperty($field, $this->getFieldValues('commerce_product', $field, $product_id, $revision_id));
    }

    // Include the number of currency fraction digits in the price.
    $currencyRepository = new CurrencyRepository();
    $value = $row->getSourceProperty('commerce_price');
    $currency_code = $value[0]['currency_code'];
    $value[0]['fraction_digits'] = $currencyRepository->get($currency_code)->getFractionDigits();
    $row->setSourceProperty('commerce_price', $value);
    return parent::prepareRow($row);
  }

}
