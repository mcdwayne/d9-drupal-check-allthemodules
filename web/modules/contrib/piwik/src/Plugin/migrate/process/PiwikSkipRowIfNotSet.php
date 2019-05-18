<?php

namespace Drupal\piwik\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate\MigrateSkipRowException;

/**
 * If the source evaluates to empty, we skip the current row.
 *
 * @MigrateProcessPlugin(
 *   id = "piwik_skip_row_if_not_set",
 *   handle_multiples = TRUE
 * )
 */
class PiwikSkipRowIfNotSet extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!isset($value[$this->configuration['module']][$this->configuration['key']])) {
      throw new MigrateSkipRowException();
    }
    return $value[$this->configuration['module']][$this->configuration['key']];
  }

}
