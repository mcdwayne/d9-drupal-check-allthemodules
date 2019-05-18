<?php

namespace Drupal\bigcommerce\Plugin\migrate\source;

use Drupal\migrate\Row;

/**
 * Gets all Product Option Fields from BigCommerce API.
 *
 * @MigrateSource(
 *   id = "bigcommerce_product_variation"
 * )
 */
class ProductVariation extends Product {

  /**
   * {@inheritdoc}
   */
  public function getYield(array $params) {
    $total_pages = 1;
    while ($params['page'] < $total_pages) {
      $params['page']++;

      // Load Default Store.
      $store = \Drupal::entityTypeManager()->getStorage('commerce_store')->loadDefault();

      $response = $this->getSourceResponse($params);
      foreach ($response->getData() as $product) {
        foreach ($product->getVariants() as $variant) {
          $variant = $variant->get();
          $variant['product_name'] = $product->getName();
          $variant['type'] = $product->getType();
          $variant['status'] = !$variant['purchasing_disabled'];
          $variant['currency_code'] = $store->getDefaultCurrencyCode();
          yield $variant;
        }
      }

      if ($params['page'] === 1) {
        $total_pages = $response->getMeta()->getPagination()->getTotalPages();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $option_values = $row->getSourceProperty('option_values');
    $option_values_ids = [];
    if (!empty($option_values)) {
      foreach ($option_values as $option_value) {
        $option_values_ids[] = $option_value->getId();
      }
    }
    $row->setSourceProperty('option_values_ids', $option_values_ids);

    return parent::prepareRow($row);
  }

}
