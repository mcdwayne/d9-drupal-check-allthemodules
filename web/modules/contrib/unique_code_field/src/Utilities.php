<?php

namespace Drupal\unique_code_field;

use Drupal\unique_code_field\Generator as Generator;

/**
 * Utilities for Unique Code Field.
 *
 * @author Alessandro Cereda <alessandro@geekworldesign.com>
 */
class Utilities {

  /**
   * PragmaRX class variable.
   *
   * @var Drupal\unique_code_field\Generator
   */
  protected $generator;

  /**
   * Inject the Random class in protected var.
   */
  public function __construct() {
    $this->generator = new Generator();
  }

  /**
   * Check if a given code is unique.
   *
   * @param string $code
   *   The code whose uniqueness we are checking.
   * @param string $entity_type
   *   The entity type we are working with.
   * @param string $field_name
   *   The name given to the field whose value has to be unique.
   *
   * @return bool
   *   A boolean that indicates if the given code is unique or not.
   */
  public static function isUnique(string $code, string $entity_type, string $field_name) {
    $entities = \Drupal::entityTypeManager()
      ->getStorage($entity_type)
      ->loadMultiple();
    if (count($entities) > 0) {
      foreach ($entities as $entity) {
        if ($entity->hasField($field_name)) {
          $value_test = $entity->get($field_name)->getValue();
          if ($value_test === $code) {
            return FALSE;
          }
        }
      }
    }
    return TRUE;
  }

  /**
   * Generate a unique code.
   *
   * @param string $type
   *   The type of the code you want to generate.
   * @param int $length
   *   The size of the code you want to generate.
   *
   * @return null|string
   *   The generated code. NULL if the requested type does not exists.
   */
  public function generateCode(string $type, int $length) {
    $code = $this->generator->generateCode($type, $length);
    return $code;
  }

}
