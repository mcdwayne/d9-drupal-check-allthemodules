<?php

namespace Drupal\tikitoki\FieldProcessor;

/**
 * Interface FieldProcessorInterface.
 *
 * @package Drupal\tikitoki\FieldProcessor
 */
interface FieldProcessorInterface {
  /**
   * Get field destination ID.
   *
   * @return string
   */
  public static function getDestinationId();

  /**
   * Get field's value.
   *
   * @return mixed
   */
  public function getValue();

}
