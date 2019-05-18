<?php

namespace Drupal\mfd;

use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\TypedData\TypedData;

/**
 * A computed property for processing text with a format.
 *
 * Required settings (below the definition's 'settings' key) are:
 *  - text source: The text property containing the to be processed text.
 */
class FieldNullComputer extends TypedData {

  /**
   * Cached processed value.
   *
   * @var string|null
   */
  protected $processed = NULL;

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    return $this->processed;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($value, $notify = TRUE) {
    $this->processed = NULL;
  }

}
