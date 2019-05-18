<?php

/**
 * @file
 * Contains \Drupal\hookalyzer\Diff\NullDiff.
 */

namespace Drupal\hookalyzer\Diff;

/**
 * Diff for NULLs. really?
 */
class NullDiff extends BaseDiff {
  public function getVisualDiff() {
    return '';
  }
}