<?php

namespace Drupal\kalastatic\Template\Loader;

/**
 * Loads templates from the filesystem.
 *
 * This loader adds Kalastatic specific paths as namespaces to the Twig
 * filesystem loader so that templates can be referenced by namespace.
 */
class KalastaticLibraryLoader extends \Twig_Loader_Filesystem {

  // Keep track of libraries that we attempt to register.
  protected $libraries = [];

  /**
   * Constructs a new ComponentsLoader object.
   */
  public function __construct() {
    // Register the namespace paths.
    foreach (kalastatic_get_namespaces() as $namespace => $path) {
      $this->libraries[] = [
        'type' => 'module',
        'name' => 'kalastatic',
        'namespace' => $namespace,
        'paths' => $path,
        'error' => FALSE
      ];
      $this->addPath($path, $namespace);
    }
  }

}
