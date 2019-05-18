<?php

use Drupal\clever_reach\Component\Utility\Initializer;

/**
 * Class CoreAutoloader.
 */
class CoreAutoloader {
  const CORE_NAMESPACE = 'CleverReach\\';

  /**
   * Indicator if core has been loaded.
   *
   * @var bool
   */
  private static $loaded = FALSE;

  /**
   * Loads CleverReach core and register core services.
   */
  public function load() {
    if (self::$loaded === FALSE) {
      $this->registerClasses();
      $this->registerServices();
      self::$loaded = TRUE;
    }
  }

  /**
   * Load CleverReach core.
   */
  private function registerClasses() {
    spl_autoload_register([$this, 'loadClass'], TRUE, TRUE);
  }

  /**
   * Registers all core services.
   */
  private function registerServices() {
    // Drupal 8.0 doesn't load all files immediately.
    if (!class_exists('\Drupal\clever_reach\Component\Utility\Initializer')) {
      require_once __DIR__ . "/../src/Component/Utility/Initializer.php";
    }

    Initializer::registerServices();
  }

  /**
   * Callback function for spl_autoload_register method. Loads single class.
   *
   * @param string $class
   *   Class load name.
   */
  private function loadClass($class) {
    if (strpos($class, self::CORE_NAMESPACE) === 0) {
      $this->requireFile($class, self::CORE_NAMESPACE);
    }
  }

  /**
   * Includes file if exist.
   *
   * @param string $class
   *   Class name.
   * @param string $namespace
   *   Namespace.
   */
  private function requireFile($class, $namespace) {
    $class = str_replace([$namespace, '\\'], ['', '/'], $class);
    $file = __DIR__ . DIRECTORY_SEPARATOR . $class . '.php';

    if (file_exists($file)) {
      require_once $file;
    }
  }

}
