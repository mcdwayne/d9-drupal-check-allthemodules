<?php

namespace Drupal\commerce_migrate_ubercart\Plugin\migrate\source\uc7;

use Drupal\node\Plugin\migrate\source\d7\NodeType;

/**
 * Gets Ubercart 7 product classes from database.
 *
 * In Ubercart 7 product classes refers to node types that are  product types.
 * @link http://www.ubercart.org/docs/user/10963/understanding_product_classes @endlink.
 *
 * @MigrateSource(
 *   id = "uc7_product_type",
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
