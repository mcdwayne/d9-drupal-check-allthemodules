<?php

namespace Drupal\ext_redirect\Service;

/**
 * Interface CurrentPathInterface.
 */
interface CurrentUrlInterface {

  /**
   * Indicates whether this path leads to admin section.
   *
   * @return bool
   */
  public function isAdminPath();

  /**
   * Get current path. Path alias is returned if exists.
   * If query params exists, are also attached to return string.
   *
   * @return string
   */
  public function getPath();

  /**
   * Get current URL scheme: http or https.
   *
   * @return string
   */
  public function getScheme();

  /**
   * Get current URL host.
   *
   * @return string
   */
  public function getHost();

}
