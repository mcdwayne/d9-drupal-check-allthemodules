<?php

use Drupal\cleverreach\Component\Utility\Initializer;

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
   * @var bool
   */
  private $loadTest;

  /**
   * CoreAutoloader constructor.
   *
   * @param bool $loadTest
   */
  public function __construct($loadTest = FALSE) {
    $this->loadTest = $loadTest;
  }

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
    if (!class_exists('\Drupal\cleverreach\Component\Utility\Initializer')) {
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
      if ($this->loadTest) {
        $this->requireFile($class, self::CORE_NAMESPACE . 'Tests\\', 'tests');
        $this->requireFile($class, self::CORE_NAMESPACE . 'Tests\\GenericTests\\', 'generic_tests');
      }

      $this->requireFile($class, self::CORE_NAMESPACE);
    }
  }

  /**
   * @param $class
   *   Class name
   * @param $namespace
   *   Namespace.
   * @param string $dir
   *   Directory of loaded class.
   */
  private function requireFile($class, $namespace, $dir = 'src') {
    $class = str_replace([$namespace, '\\'], ['', '/'], $class);
    $file = __DIR__ . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR . $class . '.php';

    if (!file_exists($file)) {
      return;
    }

    require_once $file;
  }

}
