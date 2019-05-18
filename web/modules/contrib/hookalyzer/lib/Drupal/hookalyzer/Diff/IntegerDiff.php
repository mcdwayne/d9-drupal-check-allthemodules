<?php

/**
 * @file
 * Contains \Drupal\hookalyzer\Diff\IntegerDiff.
 */

namespace Drupal\hookalyzer\Diff;

/**
 * Represents a diff between two integers.
 */
class IntegerDiff extends BaseDiff {

  /**
   * {@inheritdoc}
   */
  public function getVisualDiff() {
    if ($this->getChangeType() === self::UNCHANGED) {
      return FALSE;
    }

    return "{$this->val1} -> {$this->val2}";
  }
}