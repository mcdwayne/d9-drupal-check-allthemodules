<?php

/**
 * @file
 * Contains \Drupal\hookalyzer\Diff\ObjectDiff.
 */

namespace Drupal\hookalyzer\Diff;

/**
 * Represents a diff between two objects.
 */
class ObjectDiff extends BaseDiff {

  public function __construct($val1, $val2) {
    $this->val1 = $val1;
    $this->val2 = $val2;

    if ($val1 !== $val2) {
      $this->changeType |= self::VALUE_CHANGE;
    }

    if (spl_object_hash($val1) !== spl_object_hash($val2)) {
      $this->changeType |= self::OBJECT_INSTANCE_CHANGE;
      if (get_class($val1) !== get_class($val2)) {
        $this->changeType |= self::OBJECT_TYPE_CHANGE;
        if (!($val1 instanceof $val2 || $val2 instanceof $val1)) {
          $this->changeType |= self::OBJECT_FAMILY_CHANGE;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getVisualDiff() {
    // TODO Change all this once we recurse, yar
    if ($this->getChangeType() === self::UNCHANGED) {
      return FALSE;
    }
    else {
      return 'varied object changes';
    }
  }
}