<?php

namespace Drupal\tikitoki\FieldProcessor;

/**
 * Class EndDateFieldProcessor.
 *
 * @package Drupal\tikitoki\FieldProcessor
 */
class EndDateFieldProcessor extends BaseFieldProcessor {
  /**
   * {@inheritdoc}
   */
  protected static $destinationId = 'endDate';

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    $value = $this->field->getValue($this->viewsRow);
    $timestamp = NULL;

    if (empty($value)) {
      return '';
    }

    if (is_numeric($value)) {
      $timestamp = $value;
    }
    elseif (is_string($value)) {
      $value = new \DateTime($value);
      $timestamp = $value->getTimestamp();
      // @TODO: Add timezone support.
      $timezone = !empty($this->options['timezone'])
        ? $this->options['timezone']
        : NULL;
    }

    return !empty($timestamp)
      ? format_date($timestamp, 'custom', 'Y-m-d h:m:i', NULL, 'en')
      : '';
  }

}
