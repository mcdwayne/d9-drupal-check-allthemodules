<?php

namespace Drupal\rest_views;

/**
 * Wrapper for renderable data that will be rendered during normalization.
 *
 * @package Drupal\rest_views
 */
class RenderableData {

  /**
   * @var string
   */
  protected $data;

  /**
   * RenderableData constructor.
   *
   * @param array $data
   */
  public function __construct(array $data) {
    $this->data = $data;
  }

  /**
   * Create a renderable data object.
   *
   * @param array $data
   *
   * @return \Drupal\rest_views\RenderableData
   */
  public static function create(array $data) {
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
