<?php

namespace Drupal\commerce_migrate_ubercart\Plugin\migrate\source\uc7;

use Drupal\commerce_migrate_ubercart\Plugin\migrate\source\ProductVariationTypeTrait;

/**
 * Gets Ubercart 7 product variations from database.
 *
 * @MigrateSource(
 *   id = "uc7_product_variation_type",
 *   source_module = "uc_product"
 * )
 */
class ProductVariationType extends ProductType {

  use ProductVariationTypeTrait;

}
