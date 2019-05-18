<?php

namespace Drupal\multiversion;

use Drupal\Core\TypedData\TypedData;

/**
 * The 'new_edit' property for revision token fields.
 */
class NewEdit extends TypedData {

  /**
   * {@inheritdoc}
   */
  public function getValue($langcode = NULL) {
    if ($this->value !== NULL) {
      return $this->value;
    }
    // Fall back on TRUE as the default value.
    return TRUE;
  }

}
