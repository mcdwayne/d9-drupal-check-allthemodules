<?php

namespace Drupal\tikitoki\FieldProcessor;

/**
 * Class CategoryFieldProcessor.
 *
 * @package Drupal\tikitoki\FieldProcessor
 */
class CategoryFieldProcessor extends BaseFieldProcessor {
  /**
   * Field destination ID.
   *
   * @var string
   */
  protected static $destinationId = 'category';

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    $value = $this->field->getValue($this->viewsRow);
    if (is_array($value)) {
      $value = reset($value);
    }
    return $value;
  }

}
