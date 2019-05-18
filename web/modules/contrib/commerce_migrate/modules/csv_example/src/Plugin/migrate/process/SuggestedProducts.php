<?php

namespace Drupal\commerce_migrate_csv_example\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Builds an array keyed by title for product migration lookup.
 *
 * @MigrateProcessPlugin(
 *   id = "csv_example_suggested_products"
 * )
 */
class SuggestedProducts extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $ret = [];
    if (is_array($value)) {
      foreach ($value as $suggested) {
        if (!empty($suggested)) {
          $ret[] = [$suggested];
        }
      }
    }
    return $ret;
  }

}
