<?php

namespace Drupal\migrate_process_extras\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Call any PHP function in the input data.
 *
 * @MigrateProcessPlugin(
 *   id = "php_function"
 * )
 */
class PhpFunction extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    return call_user_func_array($this->configuration['function'], (array) $value);
  }

}
