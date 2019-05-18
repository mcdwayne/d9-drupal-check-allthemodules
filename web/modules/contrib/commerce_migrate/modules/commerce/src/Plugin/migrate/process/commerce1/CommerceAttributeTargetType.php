<?php

namespace Drupal\commerce_migrate_commerce\Plugin\migrate\process\commerce1;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Calculate the field name.
 *
 * @MigrateProcessPlugin(
 *   id = "commerce1_attribute_target_type"
 * )
 */
class CommerceAttributeTargetType extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $target_type = $row->getDestinationProperty('settings/target_type');
    // Get the commerce attribute style field name.
    if (($row->getSourceProperty('entity_type') == 'commerce_product') &&
      ($row->getSourceProperty('type') == 'taxonomy_term_reference')) {
      $instances = $row->getSourceProperty('instances');
      if ($instances) {
        // If any instance has a select widget, then this is an attribute.
        foreach ($instances as $instance) {
          $data = unserialize(($instance['data']));
          if ($data['widget']['type'] == 'options_select') {
            $target_type = 'commerce_product_attribute_value';
            break;
          }
        }
      }
      else {
        throw new MigrateSkipRowException(sprintf("No instances for attribute for destination '%s'", $destination_property));
      }
    }
    return $target_type;
  }

}
