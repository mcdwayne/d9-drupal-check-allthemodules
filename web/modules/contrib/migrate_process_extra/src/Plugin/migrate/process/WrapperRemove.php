<?php

namespace Drupal\migrate_process_extra\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Row;

/**
 * Removes a value from a wrapper, and the wrapper itself.
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
 *     plugin: wrapper_remove
 *     source: some_text_field
 *     open: (
 *     close: )
 * @endcode
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 *
 * @MigrateProcessPlugin(
 *   id = "wrapper_remove"
 * )
 */
class WrapperRemove extends ProcessPluginBase {

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
  private function deleteStringBetween($value, $open, $close) {
    $value = ' ' . $value;
    $ini = strpos($value, $open);
    // When we cannot find the open string, just return the value.
    if ($ini == 0) {
      return $value;
    }
    $ini += strlen($open);
    $len = strpos($value, $close, $ini) - $ini;
    $toDelete = substr($value, $ini, $len);
    return str_replace($open . $toDelete . $close, '', $value);
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (is_string($value)) {
      if (isset($this->configuration['open']) && isset($this->configuration['close'])) {
        // @todo
        $open = $this->configuration['open'];
        $close = $this->configuration['close'];
        $newValue = $this->deleteStringBetween($value, $open, $close);
        $newValue = trim($newValue);
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
