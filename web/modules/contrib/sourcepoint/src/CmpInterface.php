<?php

namespace Drupal\sourcepoint;

/**
 * Interface CmpInterface.
 *
 * @package Drupal\sourcepoint
 */
interface CmpInterface {

  /**
   * Check if CMP is enabled.
   *
   * @return bool
   *   Enabled status.
   */
  public function enabled();

  /**
   * Gets the privacy URL.
   *
   * @return \Drupal\Core\Url
   *   Overlay URL.
   */
  public function getUrl();

  /**
   * Gets the shim URL.
   *
   * @return string
   *   URL to shim JS.
   */
  public function getShimUrl();

  /**
   * Gets the privacy URL.
   *
   * @return array
   *   Renderable overlay.
   */
  public function getOverlay();

}
