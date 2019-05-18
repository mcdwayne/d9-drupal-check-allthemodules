<?php

/**
 * @file
 * Contains \Drupal\mpac\Plugin\Type\Selection\SelectionBroken.
 */

namespace Drupal\mpac\Plugin\Type\Selection;

/**
 * A null implementation of SelectionInterface.
 */
class SelectionBroken implements SelectionInterface {

  public function countMatchingItems($match = NULL, $match_operator = 'CONTAINS') {
    return 0;
  }

  public function getMatchingItems($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    return array();
  }

}
