<?php

/**
 * @file
 * Contains \Drupal\hookalyzer\Diff\StringDiff.
 */

namespace Drupal\hookalyzer\Diff;

/**
 * Represents a diff between two strings.
 */
class StringDiff extends BaseDiff {

  /**
   * {@inheritdoc}
   */
  public function getVisualDiff() {
    if ($this->getChangeType() === self::UNCHANGED) {
      return FALSE;
    }

    if ((strlen($this->val1) + strlen($this->val2)) / 2 <= 128) {
      return "{$this->val1}\n->\n{$this->val2}";
    }
    else {
      return 'Strings too long for preview.';
    }
  }

  public function getSimilarity() {
    similar_text($this->val1, $this->val2, $pct);
    return $pct;
  }
}