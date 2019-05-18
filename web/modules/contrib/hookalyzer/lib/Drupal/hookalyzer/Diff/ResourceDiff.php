<?php

/**
 * @file
 * Contains \Drupal\hookalyzer\Diff\ResourceDiff.
 */

namespace Drupal\hookalyzer\Diff;

/**
 * Represents a diff between two resources.
 */
class ResourceDiff extends BaseDiff {

  /**
   * {@inheritdoc}
   */
  public function getVisualDiff() {
    return print_r($this->val1, TRUE) . ' -> ' . print_r($this->val2, TRUE);
  }

}