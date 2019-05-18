<?php

namespace Drupal\commerce_migrate\Plugin\migrate\destination;

use Drupal\commerce_product\Entity\ProductType;
use Drupal\migrate\Plugin\migrate\destination\EntityConfigBase;
use Drupal\migrate\Row;

/**
 * Migrate destination for commerce product type.
 *
 * If the product type save is successful, add the 3 required fields, stores,
 * body and variation.
 *
 * @MigrateDestination(
 *   id = "entity:commerce_product_type"
 * )
 */
class CommerceProductType extends EntityConfigBase {

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    if ($ret = parent::import($row, $old_destination_id_values)) {
      $product_type = ProductType::load($row->getDestinationProperty('id'));
      commerce_product_add_stores_field($product_type);
      commerce_product_add_body_field($product_type);
      commerce_product_add_variations_field($product_type);
    }
    return $ret;
  }

}
