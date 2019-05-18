<?php

namespace Drupal\commerce_migrate_commerce\Plugin\migrate\process\commerce1;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Transforms the force and refresh mode Commerce 1 values to refresh mode.
 *
 * The source values force and refresh_mode are variables. The default value
 * for force is 'always' and the default value for refresh_mode is 'owner only'.
 * The refresh_mode value is only used when force is FALSE.
 *
 * @MigrateProcessPlugin(
 *   id = "commerce1_refresh_mode"
 * )
 */
class CommerceRefreshMode extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $new_value = NULL;
    if (is_array($value) && !empty($value)) {
      list($force, $refresh_mode) = $value;

      // If force is true then use the default 'always'.
      if ($force) {
        $new_value = 'always';
      }
      else {
        // If the refresh mode is not always then it is the default 'customer'.
        if ($refresh_mode != 'always') {
          $new_value = 'customer';
        }
      }
    }
    return $new_value;
  }

}
