<?php
/**
 * Created by PhpStorm.
 * User: metzlerd
 * Date: 1/18/2017
 * Time: 4:42 PM
 */

namespace Drupal\forena\Context;


class AppContext {
  protected $properties = [];
  public static $instance;

  public function __get($name) {
    return isset($this->properties[$name]) ? $this->properties[$name] : NULL;
  }

  public function __set($name, $value) {
    $this->properties[$name]  = $value;
  }

  /**
   * Singleton factory.
   * @return static
   */
  public static function create() {
    if (static::$instance === NULL) {
      static::$instance = new static();
    }
    return static::$instance;
  }

  public function getProperties() {
    return $this->properties;
  }

  /**
   * Wakeup method makes sure unserialized obejcts from the app context
   * are still set at the instance level.
   */
  public function __wakeup() {
    static::$instance = $this;
  }

}
