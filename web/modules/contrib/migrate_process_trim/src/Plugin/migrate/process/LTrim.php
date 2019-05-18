<?php

namespace Drupal\migrate_process_trim\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Runs PHP's ltrim() against the value.
 *
 * @MigrateProcessPlugin(
 *   id = "ltrim"
 * )
 *
 */
class LTrim extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!empty($this->configuration['mask'])) {
      return ltrim($value, $this->configuration['mask']);
    }

    return ltrim($value);
  }
}
