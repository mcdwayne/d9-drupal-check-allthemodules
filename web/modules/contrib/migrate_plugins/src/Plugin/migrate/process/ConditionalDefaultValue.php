<?php

namespace Drupal\migrate_plugins\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Provides a 'ConditionalDefaultValue' migrate process plugin.
 *
 * @MigrateProcessPlugin(
 *  id = "conditional_default_value"
 * )
 */
class ConditionalDefaultValue extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $default_value_field = $this->configuration['default_value_field'];
    $default_value = $row->getSourceProperty($default_value_field);
    return $value ?: $default_value;
  }

}
