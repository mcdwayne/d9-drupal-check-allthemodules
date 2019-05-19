<?php

namespace Drupal\convert_media_tags_to_markup\ConvertMediaTagsToMarkup;

use Drupal\convert_media_tags_to_markup\traits\Singleton;
use Drupal\convert_media_tags_to_markup\traits\CommonUtilities;

/**
 * Code to replace all code in the database.
 */
class DbReplacer {

  use Singleton;
  use CommonUtilities;

  /**
   * Log an error.
   *
   * @param string $err
   *   An error message.
   * @param string $log
   *   A log function, for example 'print_r' or 'dpm'.
   */
  protected function err(string $err, $log = 'print_r') {
    $log('An error occurred: ' . $err . PHP_EOL);
    $this->err = TRUE;
  }

  /**
   * Replace all instances of legacy media tags in the database to images.
   *
   * Outputs the results using print_r(). This is designed to be used via
   * drush.
   *
   * @param string $type
   *   A type such as node.
   * @param string $bundle
   *   A bundle such as article.
   * @param bool $simulate
   *   Whether or not to Simulate the results.
   * @param string $log
   *   For example "print_r" or "dpm".
   */
  public function replaceAll(string $type, string $bundle, bool $simulate = TRUE, string $log = 'print_r') {
    try {
      foreach ($this->getAllEntities($type, $bundle) as $entity) {
        try {
          $entity->process($simulate, $log);
        }
        catch (\Throwable $t) {
          $this->err($t->getMessage(), $log);
        }
      }
    }
    catch (\Throwable $t) {
      $this->err($t->getMessage() . ' at ' . $t->getFile() . ':' . $t->getLine(), $log);
    }

    if (!empty($this->err)) {
      $log('Exiting with error code 1 because at least one error occurred during processing.' . PHP_EOL);
      exit(1);
    }
  }

}
