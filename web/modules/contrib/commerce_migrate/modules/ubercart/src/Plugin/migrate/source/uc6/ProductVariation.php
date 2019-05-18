<?php

namespace Drupal\commerce_migrate_ubercart\Plugin\migrate\source\uc6;

use Drupal\commerce_migrate_ubercart\Plugin\migrate\source\ProductVariationTrait;
use Drupal\node\Plugin\migrate\source\d6\Node;

/**
 * Ubercart 6  product variation source.
 *
 * @MigrateSource(
 *   id = "uc6_product_variation",
 *   source_module = "uc_product"
 * )
 */
class ProductVariation extends Node {

  use ProductVariationTrait;

}
