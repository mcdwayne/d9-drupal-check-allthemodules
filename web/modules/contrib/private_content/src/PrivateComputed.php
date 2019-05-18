<?php

namespace Drupal\private_content;

use Drupal\Core\TypedData\TypedData;

/**
 * A computed property for processing private value based on node content type settings.
 */
class PrivateComputed extends TypedData {

  /**
   * Cached computed value.
   */
  protected $computed = NULL;

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    if ($this->computed === NULL) {
      $item = $this->getParent();
      $stored = $item->stored;

      $list = $item->getParent();
      if (($stored === NULL) || $list->isLocked()) {
        $this->computed = $list->getDefault();
      }
      else {
        $this->computed = $stored;
      }
    }

    return $this->computed;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($value, $notify = TRUE) {
    $this->computed = $value;

    // Notify the parent of any changes.
    if ($notify && isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
  }

}
