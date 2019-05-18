<?php

/**
 * @file
 * Contains \Drupal\hookalyzer\Diff\Iterator\ComparableIterator.
 */

namespace Drupal\hookalyzer\Diff\Iterator;

/**
 * An iterator useful on the "right" side in set comparisons.
 *
 * In addition to being seekable, ComparableIterators should keep track of the
 * set of keys not yet visited. This allows easy discovery of collection items
 * that exist on the right, but not the left.
 */
interface ComparableIterator extends \SeekableIterator {

  /**
   * Returns the set of keys that have not yet been visited.
   *
   * @return array
   */
  public function remainingKeys();
}