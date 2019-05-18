<?php

/**
 * @file
 * Contains \Drupal\mpac\Plugin\Type\Selection\SelectionInterface.
 */

namespace Drupal\mpac\Plugin\Type\Selection;

/**
 * Interface definition for Multi-path autocomplete selection plugins.
 */
interface SelectionInterface {

  /**
   * Returns a list of matching items.
   *
   * @return array
   *   An array of path items. Keys are the system paths of the items and
   *   values are (safe HTML) titles of the corresponding pages.
   */
  public function getMatchingItems($match = NULL, $match_operator = 'CONTAINS', $limit = 0);

  /**
   * Counts items that matches against the given string.
   *
   * @return int
   *   The number of matching items.
   */
  public function countMatchingItems($match = NULL, $match_operator = 'CONTAINS');
}
