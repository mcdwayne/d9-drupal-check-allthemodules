<?php

namespace Drupal\library;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Library action entities.
 */
interface LibraryActionInterface extends ConfigEntityInterface {
  // Add get/set methods for your configuration properties here.
  const NO_CHANGE = 0;
  const CHANGE_TO_UNAVAILABLE = 1;
  const CHANGE_TO_AVAILABLE = 2;

  /**
   * The action itself.
   */
  public function action();

}
