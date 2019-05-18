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
 *   id = "skip_on_empty_array"
 * )
 */
class SkipOnEmptyArray extends SkipOnEmpty {

  /**
   * {@inheritdoc}
   */
  public function row($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if ($this->isEmpty($value)) {
      $message = !empty($this->configuration['message']) ? $this->configuration['message'] : '';
      throw new MigrateSkipRowException($message);
    }
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function process($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if ($this->isEmpty($value)) {
      throw new MigrateSkipProcessException();
    }
    return $value;
  }

  /**
   * Checks if an array consists of only empty() values.
   *
   * @param &$in
   *   The input. Usually an array, but can be mixed.
   *
   * @return bool
   *   TRUE if the input is empty() or is an array of empty() values. FALSE otherwise.
   */
  protected function isEmpty(&$in) {
    // If the input is not an array, just check with empty().
    if (!is_array($in)) {
      return empty($in);
    }

    // If the input is an array, go through each item.
    foreach ($in as $key => $value) {
      // If the item is an array...
      if (is_array($value)) {
        // Check if any of it's values are empty.
        if (!$this->isEmpty($value)) {
          // Stop when a non-empty value is found.
          return FALSE;
        }
      }
      elseif (!empty($value)) {
        // If not an array, stop if the value is non-empty.
        return FALSE;
      }
    }

    // Still here? The array is empty.
    return TRUE;
  }

}
