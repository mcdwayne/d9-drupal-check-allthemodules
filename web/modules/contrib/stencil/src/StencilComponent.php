<?php

namespace Drupal\stencil;

/**
 * Defines a Stencil Component object.
 */
class StencilComponent {

  /**
   * The namespace.
   *
   * @var string
   */
  public $namespace;

  /**
   * The tag name.
   *
   * @var string
   */
  public $tag;

  /**
   * An array of properties (attributes).
   *
   * @var string[]
   */
  public $props;

  /**
   * Constructs a new StencilComponent object.
   *
   * @param string $namespace
   *   The namespace.
   * @param string $tag
   *   The tag name.
   * @param string[] $props
   *   An array of properties (attributes).
   */
  public function __construct($namespace, $tag, $props) {
    $this->namespace = $namespace;
    $this->tag = $tag;
    $this->props = $props;
  }

}
