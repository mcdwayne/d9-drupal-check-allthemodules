<?php

namespace Drupal\migrate_process_vardump\Plugin\migrate\process;


use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\Core\Database\Database;

/**
 * Dumps the input value to stdout. Passes the rest through.
 *
 * @MigrateProcessPlugin(
 *   id = "vardump"
 * )
 *
 */
class Vardump extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    if (!empty($this->configuration['header'])) {
      echo $this->configuration['header'] . ': ';
    }

    var_dump($value);

    return $value;
  }

}
