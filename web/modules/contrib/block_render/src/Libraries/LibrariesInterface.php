<?php
/**
 * @file
 * Contains Drupal\block_render\Response\LibraryResponseInterface.
 */

namespace Drupal\block_render\Libraries;

/**
 * The asset response data.
 */
interface LibrariesInterface extends \IteratorAggregate {

  /**
   * Returns the asset libraries.
   *
   * @return array
   *   Array of Libraries.
   */
  public function getLibraries();

}
