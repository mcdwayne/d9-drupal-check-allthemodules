<?php

namespace Drupal\matomo\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * This plugin flattens the custom variables array.
 *
 * @MigrateProcessPlugin(
 *   id = "matomo_custom_vars"
 * )
 */
class MatomoCustomVars extends ProcessPluginBase {

  /**
   * Flatten custom vars array.
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    list($matomo_custom_vars) = $value;

    return isset($matomo_custom_vars['slots']) ? $matomo_custom_vars['slots'] : [];
  }

}
