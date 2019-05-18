<?php

namespace Drupal\evergreen;

/**
 * Defines the EvergreenInterface for Evergreen plugins.
 */
interface EvergreenInterface {

  /**
   * Get the bundle options for this entity.
   */
  public function getBundleOptions();

}
