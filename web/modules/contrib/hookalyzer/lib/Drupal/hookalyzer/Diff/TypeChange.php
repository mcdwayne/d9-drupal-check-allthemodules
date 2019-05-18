<?php

/**
 * @file
 * Contains \Drupal\hookalyzer\Diff\TypeChange.
 */

namespace Drupal\hookalyzer\Diff;

/**
 * Represents a diff for two values of a different type.
 */
class TypeChange extends BaseDiff {

  /**
   * {@inheritdoc}
   */
  public function getChangeType() {
    if (is_null($this->val1) || is_null($this->val2)) {
      return is_null($this->val1) ? self::ADDED : self::REMOVED;
    }
    return self::TYPE_CHANGE;
  }

  /**
   * {@inheritdoc}
   */
  public function getVisualDiff() {
    return FALSE;
  }

  public function getType() {
    switch ($this->getChangeType()) {
      case self::ADDED:
        return Diff::getTypeString($this->val2);
      case self::REMOVED:
        return Diff::getTypeString($this->val1);
      default:
        return Diff::getTypeString($this->val1) . ' -> ' . Diff::getTypeString($this->val2);
    }
  }
}