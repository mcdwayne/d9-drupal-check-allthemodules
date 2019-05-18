<?php

namespace Drupal\commerce_migrate_ubercart\Plugin\migrate\source\uc6;

use Drupal\node\Plugin\migrate\source\d6\NodeType;

/**
 * Gets Ubercart 6 product classes from database.
 *
 * In Ubercart 6 product classes refers to node types that are  product types.
 * @link http://www.ubercart.org/docs/user/10963/understanding_product_classes @endlink.
 *
 * @MigrateSource(
 *   id = "uc6_product_type",
 *   source_module = "uc_product"
 * )
 */
class ProductType extends NodeType {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();
    $query->condition('module', 'uc_product%', 'LIKE');
    return $query;
  }

}
