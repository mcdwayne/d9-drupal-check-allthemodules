<?php

namespace Drupal\bigcommerce\Plugin\migrate\source;

use Drupal\migrate\Row;

/**
 * Gets all Products from BigCommerce API.
 *
 * @MigrateSource(
 *   id = "bigcommerce_product"
 * )
 */
class Product extends BigCommerceSource {

  /**
   * {@inheritdoc}
   */
  protected $trackChanges = TRUE;

  /**
   * {@inheritdoc}
   */
  public function getSourceResponse(array $params) {
    $params['include'] = 'variants,custom_fields,images';
    return $this->catalogApi->getProducts($params);
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $name = $row->getSourceProperty('name');
    $images = $row->getSourceProperty('images');
    if (!empty($images)) {
      foreach ($images as &$image) {
        $image = $image->get();
        $image['name'] = $name;
      }
      $row->setSourceProperty('images', $images);
    }
    return parent::prepareRow($row);
  }

}
