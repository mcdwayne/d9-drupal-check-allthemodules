<?php

/**
 * @file
 * Contains \Drupal\hookalyzer\Diff\BooleanDiff.
 */

namespace Drupal\hookalyzer\Diff;

/**
 * Represents a diff between two booleans.
 */
class BooleanDiff extends BaseDiff {

  /**
   * {@inheritdoc}
   */
  public function getVisualDiff() {
    if ($this->getChangeType() === self::UNCHANGED) {
      return FALSE;
    }

    return $this->val1 ? "TRUE -> FALSE" : "FALSE -> TRUE";
  }
}