<?php

declare(strict_types = 1);

namespace Drupal\commerce_klarna_payments\Klarna;

use Drupal\commerce_klarna_payments\Klarna\Data\ObjectInterface;

/**
 * Base class for requests.
 */
abstract class RequestBase implements ObjectInterface {

  /**
   * The container for data used in request.
   *
   * @var array
   */
  protected $data = [];

  /**
   * {@inheritdoc}
   */
  public function toArray() : array {
    $data = [];
    foreach ($this->data as $key => $value) {
      $normalized = $value;

      if ($value instanceof ObjectInterface) {
        // Normalize object values.
        $normalized = $value->toArray();
      }

      // Handle multivalue fields.
      if (is_array($value)) {

        $normalized = [];

        foreach ($value as $k => $v) {
          // Some values are just regular arrays that should be sent
          // just as they are (like custom_payment_method_ids).
          if (!$v instanceof ObjectInterface) {
            $normalized[] = $v;

            continue;
          }
          $normalized[] = $v->toArray();
        }
      }
      // Convert empty arrays to NULL.
      if (is_array($normalized) && !$normalized) {
        $normalized = NULL;
      }
      $data[$key] = $normalized;
    }

    return $data;
  }

}
