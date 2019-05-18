<?php

namespace Drupal\bigcommerce\Plugin\migrate\source;

/**
 * Gets all Product Brands from BigCommerce API.
 *
 * @MigrateSource(
 *   id = "bigcommerce_product_brand"
 * )
 */
class ProductBrand extends BigCommerceSource {

  /**
   * {@inheritdoc}
   */
  protected $trackChanges = TRUE;

  /**
   * {@inheritdoc}
   */
  public function getSourceResponse(array $params) {
    return $this->catalogApi->getBrands($params);
  }

}
