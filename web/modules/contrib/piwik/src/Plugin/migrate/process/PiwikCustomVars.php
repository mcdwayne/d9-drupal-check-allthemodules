<?php

namespace Drupal\piwik\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * This plugin flattens the custom variables array.
 *
 * @MigrateProcessPlugin(
 *   id = "piwik_custom_vars"
 * )
 */
class PiwikCustomVars extends ProcessPluginBase {

  /**
   * Flatten custom vars array.
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    list($piwik_custom_vars) = $value;

    return isset($piwik_custom_vars['slots']) ? $piwik_custom_vars['slots'] : [];
  }

}
