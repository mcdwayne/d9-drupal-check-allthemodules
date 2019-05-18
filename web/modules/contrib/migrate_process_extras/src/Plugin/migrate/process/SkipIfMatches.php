<?php

namespace Drupal\migrate_process_extras\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Allows us to skip or included certain patterns.
 *
 * @MigrateProcessPlugin(
 *   id = "skip_if_matches"
 * )
 */
class SkipIfMatches extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $match = (bool) preg_match($this->configuration['pattern'], $value);
    $skip_on_match = empty($this->configuration['inverse']);
    if ($match && $skip_on_match) {
      throw new MigrateSkipProcessException();
    }
    elseif (!$match && !$skip_on_match) {
      throw new MigrateSkipProcessException();
    }
    return $value;
  }

}
