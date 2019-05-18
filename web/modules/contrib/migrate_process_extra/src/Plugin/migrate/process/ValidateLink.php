<?php

namespace Drupal\migrate_process_extra\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Checks if a link is not broken.
 *
 * @MigrateProcessPlugin(
 *   id = "validate_link"
 * )
 */
class ValidateLink extends ProcessPluginBase {

  /**
   * Checks if a link does not return 404.
   *
   * @param string $url
   *   The url to be checked.
   *
   * @return bool
   *   URL is not 404.
   */
  private function checkLink($url) {
    $exists = TRUE;
    $file_headers = @get_headers($url);
    if (!$file_headers || $file_headers[0] == 'HTTP/1.1 404 Not Found') {
      $exists = FALSE;
    }
    return $exists;
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (is_string($value)) {
      $value = trim($value);
      if ($this->checkLink($value)) {
        return $value;
      }
      else {
        throw new MigrateException(sprintf('%s not found (404).', var_export($value, TRUE)));
      }
    }
    else {
      throw new MigrateException(sprintf('%s is not a string.', var_export($value, TRUE)));
    }
  }

}
