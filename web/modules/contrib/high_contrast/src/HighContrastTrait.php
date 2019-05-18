<?php

namespace Drupal\high_contrast;

/**
 * This is an abstraction wrapper for controlling high contrast.
 *
 * This class is provided to allow abstraction of the detection and setting of
 * high contrast mode. Currently it is configured to use the session, but this
 * allows for easier change if that may be required at some point.
 */
trait HighContrastTrait {

  /**
   * Return if high contrast is enabled or not.
   *
   * @return bool
   *   TRUE if enabled, FALSE otherwise.
   */
  static function high_contrast_enabled() {
    return !empty($_SESSION['high_contrast']['enabled']) ? TRUE : FALSE;
  }

  /**
   * Enables high contrast mode.
   */
  function enable_high_contrast() {
    $_SESSION['high_contrast']['enabled'] = TRUE;
  }
  /**
   * Disables high contrast mode.
   */
  function disable_high_contrast() {
    $_SESSION['high_contrast']['enabled'] = FALSE;
  }

}
