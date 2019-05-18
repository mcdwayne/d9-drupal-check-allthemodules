<?php

namespace Drupal\drupal_inquicker\Inquicker;

use Drupal\drupal_inquicker\Source\Source;
use Drupal\drupal_inquicker\traits\CommonUtilities;
use Drupal\drupal_inquicker\traits\DependencyInjection;
use Drupal\drupal_inquicker\traits\Singleton;

/**
 * Represents the Inquicker API.
 */
class Inquicker {

  use CommonUtilities;
  use DependencyInjection;
  use Singleton;

  /**
   * Testable implementation of hook_requirements().
   */
  public function hookRequirements($phase) : array {
    if ($phase != 'runtime') {
      return [];
    }
    $requirements = [];
    $requirements += $this->requirementsFormatter($phase)->format($this->sources());
    return $requirements;
  }

  /**
   * Get a source for Inquicker.
   *
   * @param string $key
   *   A source key which is in your settings.php file, like "default".
   */
  public function source(string $key) : Source {
    return $this->sources()->findByKey($key);
  }

}
