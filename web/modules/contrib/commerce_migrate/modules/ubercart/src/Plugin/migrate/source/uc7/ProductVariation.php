<?php

namespace Drupal\commerce_migrate_ubercart\Plugin\migrate\source\uc7;

use Drupal\commerce_migrate_ubercart\Plugin\migrate\source\ProductVariationTrait;
use Drupal\node\Plugin\migrate\source\d7\Node;

/**
 * Ubercart 7 product variation source.
 *
 * @MigrateSource(
 *   id = "uc7_product_variation",
 *   source_module = "uc_product"
 * )
 */
class ProductVariation extends Node {

  use ProductVariationTrait;

}
