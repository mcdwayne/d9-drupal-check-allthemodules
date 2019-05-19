<?php

namespace Drupal\tikitoki\FieldProcessor;

/**
 * Class FullTextFieldProcessor.
 *
 * @package Drupal\tikitoki\FieldProcessor
 */
class FullTextFieldProcessor extends BaseFieldProcessor {
  /**
   * {@inheritdoc}
   */
  protected static $destinationId = 'fullText';

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    return $this->field->advancedRender($this->viewsRow);
  }

}
