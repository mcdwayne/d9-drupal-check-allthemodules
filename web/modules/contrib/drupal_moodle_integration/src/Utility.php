<?php

namespace Drupal\moodle_integration;

/**
 * Class CustomService.
 */

class Utility {
    public static function config() {
      $config =  \Drupal::config('moodle.settings');
      return $config->get('url');
  }
}
