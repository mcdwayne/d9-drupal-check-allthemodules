<?php

namespace Drupal\search_365\Serializer;

use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer;

/**
 * Creates a serializer for search results.
 */
class ResultSetSerializerFactory {

  /**
   * Creates a Serializer object.
   *
   * @return \Symfony\Component\Serializer\Serializer
   *   Returns the Serializer object.
   */
  public static function create() {
    $encoders = [
      new JsonEncoder(),
    ];
    $normalizers = [
      new ResultSetNormalizer(),
      new ResultNormalizer(),
    ];
    return new Serializer($normalizers, $encoders);
  }

}
