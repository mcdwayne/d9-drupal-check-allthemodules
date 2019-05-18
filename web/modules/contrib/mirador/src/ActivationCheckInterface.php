<?php

/**
 * @file
 * ActivationCheckInterface.
 */

namespace Drupal\mirador;

/**
 * An interface for checking if mirador should be active.
 */
interface ActivationCheckInterface {

  /**
   * Check if mirador should be activated for the current page.
   *
   * @return bool
   *   If mirador should be active.
   */
  public function isActive();

}
