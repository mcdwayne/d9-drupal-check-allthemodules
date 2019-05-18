<?php

namespace Drupal\navigation_blocks;

use Drupal\Core\Url;

/**
 * Interface definition for a path matcher.
 *
 * @package Drupal\navigation_blocks
 */
interface PathMatcherInterface {

  /**
   * Match path to a string of preferred paths for the back button.
   *
   * @param string $path
   *   Path to match with.
   * @param string $preferredPaths
   *   Preferred paths to navigate back to.
   *
   * @return bool
   *   Whether the path matches any preferred path.
   */
  public function matchPath(string $path, string $preferredPaths): bool;

  /**
   * Validate if the current path is valid.
   *
   * @param \Drupal\Core\Url $url
   *   The url.
   *
   * @return bool
   *   TRUE if it validates.
   */
  public function validateCurrentPath(Url $url): bool;

}
