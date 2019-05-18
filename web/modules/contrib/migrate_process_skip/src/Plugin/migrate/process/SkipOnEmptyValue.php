<?php


namespace Drupal\migrate_process_skip\Plugin\migrate\process;

use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate\Plugin\migrate\process\SkipOnEmpty;


/**
 * The same as skip_on_empty, but tests with empty().
 *
 * @MigrateProcessPlugin(
 *   id = "skip_on_empty_value"
 * )
 */
class SkipOnEmptyValue extends SkipOnEmpty {

  /**
   * {@inheritdoc}
   */
  public function row($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (empty($value)) {
      $message = !empty($this->configuration['message']) ? $this->configuration['message'] : '';
      throw new MigrateSkipRowException($message);
    }
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function process($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (empty($value)) {
      throw new MigrateSkipProcessException();
    }
    return $value;
  }

}
