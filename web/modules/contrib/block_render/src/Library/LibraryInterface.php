<?php
/**
 * @file
 * Contains Drupal\block_render\Library\Library.
 */

namespace Drupal\block_render\Library;

/**
 * Single Library.
 */
interface LibraryInterface {

  /**
   * Gets the Name.
   *
   * @return string
   *   Current library name.
   */
  public function getName();

  /**
   * Gets the Version.
   *
   * @return string
   *   Current library version.
   */
  public function getVersion();

}
