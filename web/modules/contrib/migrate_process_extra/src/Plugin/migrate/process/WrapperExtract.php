<?php

namespace Drupal\migrate_process_extra\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Row;

/**
 * Extracts a value from a wrapper.
 *
 * Available configuration keys:
 * - source: The input value - must be an array.
 * - open: The string that defines the opening of the wrapper.
 * - close: The string that defines the closing of the wrapper.
 *
 * Examples:
 * @code
 * process:
 *   new_text_field:
 *     plugin: wrapper_extract
 *     source: some_text_field
 *     open: (
 *     close: )
 * @endcode
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 *
 * @MigrateProcessPlugin(
 *   id = "wrapper_extract"
 * )
 */
class WrapperExtract extends ProcessPluginBase {

  /**
   * Extracts a string between two strings.
   *
   * @todo cover multiple instances + provide first, last, all via configuration
   *
   * @param string $value
   *   Full string.
   * @param string $open
   *   Opening string.
   * @param string $close
   *   Closing string.
   *
   * @return bool|string
   *   Extracted string.
   */
  private function getStringBetween($value, $open, $close) {
    $value = ' ' . $value;
    $ini = strpos($value, $open);
    if ($ini == 0) {
      return '';
    }
    $ini += strlen($open);
    $len = strpos($value, $close, $ini) - $ini;
    return substr($value, $ini, $len);
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (is_string($value)) {
      if (isset($this->configuration['open']) && isset($this->configuration['close'])) {
        $open = $this->configuration['open'];
        $close = $this->configuration['close'];
        $newValue = $this->getStringBetween($value, $open, $close);
        return $newValue;
      }
      else {
        throw new MigrateException(sprintf('A wrapper (open and close) must be provided via configuration.', var_export($value, TRUE)));
      }
    }
    else {
      throw new MigrateException(sprintf('%s is not a string.', var_export($value, TRUE)));
    }
  }

}
