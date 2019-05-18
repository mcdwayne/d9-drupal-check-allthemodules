<?php

/**
 * @file
 * Contains \Drupal\hookalyzer\Diff\BaseDiff.
 */

namespace Drupal\hookalyzer\Diff;

/**
 * Base class for diffs.
 */
abstract class BaseDiff implements DiffInterface {

  protected $val1;
  protected $val2;
  protected $changeType = DiffInterface::UNCHANGED;

  /**
   * {@inheritdoc}
   */
  public function __construct($val1, $val2) {
    $this->val1 = $val1;
    $this->val2 = $val2;
    $this->changeType = $this->val1 === $this->val2 ? self::UNCHANGED : self::VALUE_CHANGE;
  }

  /**
   * {@inheritdoc}
   */
  public function getChangeType() {
    return $this->changeType;
  }

  public function getType() {
    if (($this->getChangeType() & self::TYPE_CHANGE) == 0) {
      return Diff::getTypeString($this->val1);
    }
    else {
      return Diff::getTypeString($this->val1) . ' -> ' . Diff::getTypeString($this->val2);
    }
  }
}