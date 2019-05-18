<?php

namespace Drupal\navigation_blocks;

use Drupal\Core\Url;

/**
 * Interface definition for a back button manager.
 *
 * @package Drupal\navigation_blocks
 */
interface BackButtonManagerInterface {

  /**
   * Add link element attributes such as classes to the link.
   *
   * @param array $link
   *   Render array for the link.
   * @param bool $useJavascript
   *   Use javascript for the back link.
   */
  public function addLinkAttributes(array &$link, $useJavascript = FALSE): void;

  /**
   * Get the link.
   *
   * @param \Drupal\Core\Url $url
   *   Url to link to.
   * @param string $text
   *   Text to show on the link.
   * @param bool $useJavascript
   *   Use javascript for the back link.
   *
   * @return array
   *   Render array for the link.
   */
  public function getLink(Url $url, string $text, $useJavascript = FALSE): array;

  /**
   * Get the preferred link.
   *
   * @param string $preferredPaths
   *   Preferred paths to navigate to.
   * @param bool $useJavascript
   *   Use javascript for the back link.
   *
   * @return array
   *   Render array for the link.
   */
  public function getPreferredLink(string $preferredPaths, $useJavascript = FALSE): array;

  /**
   * Get the referer path.
   *
   * @return string
   *   The referer path.
   */
  public function getRefererPath(): string;

  /**
   * Get whether we are on a canonical path.
   *
   * @return bool
   *   Whether the curent route is a canonical path.
   */
  public function isCanonicalPath(): bool;

}
