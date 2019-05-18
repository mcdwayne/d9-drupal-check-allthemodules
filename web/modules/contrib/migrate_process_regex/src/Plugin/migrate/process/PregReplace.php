<?php

namespace Drupal\migrate_process_regex\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Replaces a pattern in text with a given value.
 *
 * @MigrateProcessPlugin(
 *   id = "preg_replace"
 * )
 *
 */
class PregReplace extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (empty($value)) {
      return $value;
    }

    $pattern = '/' . $this->configuration['pattern'] . '/';

    $replace = isset($this->configuration['replace']) ? $this->configuration['replace'] : '';

    $new_value  = preg_replace($pattern, $replace, $value);

    return $new_value;
  }
}
