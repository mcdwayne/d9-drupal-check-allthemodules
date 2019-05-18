<?php

namespace Drupal\bigcommerce\Plugin\migrate\source;

/**
 * Gets all Product Types from BigCommerce API.
 *
 * @MigrateSource(
 *   id = "bigcommerce_product_variation_type"
 * )
 */
class ProductVariationType extends BigCommerceSource {

  /**
   * {@inheritdoc}
   */
  public function getYield(array $params) {
    foreach ($this->getVariationTypes() as $type) {
      yield $type;
    }
  }

  /**
   * Returns all the product variation types.
   *
   * @return array
   *   The list of product variation types.
   */
  protected function getVariationTypes() {
    return [
      [
        'name' => 'physical',
        'label' => 'Physical product',
        'orderItemType' => 'default',
      ],
      [
        'name' => 'digital',
        'label' => 'Downloadable product',
        'orderItemType' => 'default',
      ],
    ];
  }

}
