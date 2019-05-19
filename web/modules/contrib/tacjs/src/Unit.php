<?php

namespace Drupal\tacjs;

use Drupal\tacjs\TacjsSettings;
/**
 * Defines a Unit class.
 */
class Unit extends TacjsSettings {


  public function __call($method, array $args = array()) {
    if (!method_exists($this, $method))
      throw new BadMethodCallException("method '$method' does not exist");
    return call_user_func_array(array($this, $method), $args);
  }

}