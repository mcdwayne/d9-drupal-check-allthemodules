<?php

namespace Drupal\cmlapi\Hook;

/**
 * Hook Cron.
 */
class Cron {

  /**
   * Hook.
   */
  public static function hook() {
    $config = \Drupal::config('cmlapi.mapsettings');
    if ($config->get('cleaner-cron')) {
      $cleaner = \Drupal::service('cmlapi.cleaner');
      $cleaner->clean();
    }
  }

}
