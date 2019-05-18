<?php

namespace Drupal\bigcommerce\Plugin\migrate\source;

/**
 * Gets all Product Types.
 *
 * @MigrateSource(
 *   id = "bigcommerce_product_type"
 * )
 */
class ProductType extends BigCommerceSource {

  /**
   * {@inheritdoc}
   */
  public function getYield(array $params) {
    foreach ($this->getProductTypes() as $type) {
      yield $type;
    }
  }

  /**
   * Get all the Product Types.
   *
   * @return array
   *   The list of product Types.
   */
  protected function getProductTypes() {
    return [
      [
        'name' => 'physical',
        'label' => 'Physical product',
        'variation_type' => 'physical',
      ],
      [
        'name' => 'digital',
        'label' => 'Downloadable product',
        'variation_type' => 'digital',
      ],
    ];
  }

}
