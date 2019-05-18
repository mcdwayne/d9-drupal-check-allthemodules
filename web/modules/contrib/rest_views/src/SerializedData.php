<?php

namespace Drupal\rest_views;

/**
 * Wrapper for passing serialized data through render arrays.
 *
 * @see \Drupal\rest_views\Normalizer\DataNormalizer
 */
class SerializedData {

  /**
   * @var string
   */
  protected $data;

  /**
   * SerializedData constructor.
   *
   * @param mixed $data
   */
  public function __construct($data) {
    $this->data = $data;
  }

  /**
   * Create a serialized data object.
   *
   * @param mixed $data
   *
   * @return \Drupal\rest_views\SerializedData
   */
  public static function create($data) {
    if ($data instanceof static) {
      return $data;
    }
    return new static($data);
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    // This must not be empty.
    return '[...]';
  }

  /**
   * @return mixed
   */
  public function getData() {
    return $this->data;
  }

}
