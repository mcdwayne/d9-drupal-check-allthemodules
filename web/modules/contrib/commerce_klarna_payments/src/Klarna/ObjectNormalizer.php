<?php

declare(strict_types = 1);

namespace Drupal\commerce_klarna_payments\Klarna;

/**
 * Converts given object to array.
 */
trait ObjectNormalizer {

  /**
   * Converts an object to array.
   *
   * @return array
   *   The data.
   */
  public function toArray() : array {
    if (!property_exists($this, 'data')) {
      throw new \LogicException('$this->data not defined.');
    }
    return array_filter($this->data, function ($value) {
      return $value !== NULL && $value !== '';
    });
  }

}
