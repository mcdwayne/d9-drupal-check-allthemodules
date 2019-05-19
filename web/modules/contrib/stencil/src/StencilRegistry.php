<?php

namespace Drupal\stencil;

/**
 * Defines a Stencil Registry object.
 */
class StencilRegistry {

  /**
   * The root directory of this registry.
   *
   * @var string
   */
  public $root;

  /**
   * The namespace.
   *
   * @var string
   */
  public $namespace;

  /**
   * An array of components in the registry file format.
   *
   * @var string
   */
  public $components;

  /**
   * The path to the loader.
   *
   * @var string
   */
  public $loader;

  /**
   * The path to the core include.
   *
   * @var string
   */
  public $core;

  /**
   * The path to the core include, with polyfills.
   *
   * @var string
   */
  public $corePolyfilled;

  /**
   * Constructs a new StencilRegistry object.
   *
   * @param string $root
   *   The relative root directory of this registry.
   * @param string $namespace
   *   The namespace.
   * @param string $components
   *   An array of components in the registry file format.
   * @param string $loader
   *   The path to the loader.
   * @param string $core
   *   The path to the core include.
   * @param string $corePolyfilled
   *   The path to the core include, with polyfills.
   */
  public function __construct($root, $namespace, $components, $loader, $core, $corePolyfilled) {
    $this->root = $root;
    $this->namespace = $namespace;
    $this->components = $components;
    $this->loader = $loader;
    $this->core = $core;
    $this->corePolyfilled = $corePolyfilled;
  }

}
