<?php

namespace Drupal\commerce_migrate_ubercart\Plugin\migrate\source\uc6;

use Drupal\commerce_migrate_ubercart\Plugin\migrate\source\ProductVariationTypeTrait;

/**
 * Gets Ubercart 6 product variations from database.
 *
 * @MigrateSource(
 *   id = "uc6_product_variation_type",
 *   source_module = "uc_product"
 * )
 */
class ProductVariationType extends ProductType {

  use ProductVariationTypeTrait;

}
