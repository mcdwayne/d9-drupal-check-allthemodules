<?php

namespace Drupal\bigcommerce\Plugin\migrate\source;

/**
 * Gets all Product Categories from BigCommerce API.
 *
 * @MigrateSource(
 *   id = "bigcommerce_product_category"
 * )
 */
class ProductCategory extends BigCommerceSource {

  /**
   * {@inheritdoc}
   */
  protected $trackChanges = TRUE;

  /**
   * {@inheritdoc}
   */
  public function getSourceResponse(array $params) {
    return $this->catalogApi->getCategories($params);
  }

}
