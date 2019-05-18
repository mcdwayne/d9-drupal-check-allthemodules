<?php

declare(strict_types = 1);

namespace Drupal\commerce_klarna_payments\Klarna\Data;

/**
 * API object.
 */
interface ObjectInterface {

  /**
   * Converts values to array.
   *
   * @return array
   *   The values.
   */
  public function toArray() : array;

}
