<?php

/**
 * Interface class for Data contexts.
 * @author metzlerd
 * The basic context type implements
 */
namespace Drupal\forena\Context;

class Context {
  private $values;

  public function __construct() {
    $this->values = array();
  }

  public function getValue($key) {
    $value = @$this->values[$key];
    return $value;
  }

  public function setValue($key, $value) {
    $this->values[$key] = $value;
  }

  /**
   * @param string $name
   *   Paramater to get. 
   * Create getter and setter so that you can act as objects.
   */
  public function __get($name) {
    $this->getValue($name);
  }

  /**
   * @param $name
   * @param $value
   * General getter and setter for key value pairs
   */
  public function __set($name, $value) {
    $this->setValue($name, $value);
  }

}