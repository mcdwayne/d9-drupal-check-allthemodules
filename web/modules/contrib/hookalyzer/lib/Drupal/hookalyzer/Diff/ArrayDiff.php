<?php

/**
 * @file
 * Contains \Drupal\hookalyzer\Diff\ArrayDiff.
 */

namespace Drupal\hookalyzer\Diff;

/**
 * Represents a diff between two arrays.
 */
class ArrayDiff extends BaseDiff {

  protected $diffs = array();

  /**
   * {@inheritdoc}
   */
  public function getVisualDiff() {
    // TODO Change all this once we recurse, yar
    switch ($this->getChangeType()) {
      case self::VALUE_CHANGE:
        return 'modified';
      default:
        return FALSE;
    }
  }
}