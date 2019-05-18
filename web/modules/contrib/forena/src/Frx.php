<?php
/**
 * Created by PhpStorm.
 * User: metzlerd
 * Date: 4/7/16
 * Time: 8:54 PM
 */

namespace Drupal\forena;


class Frx {
  
  use FrxAPI;
  
  private static $instance;

  /**
   * Forena API instance to expose traits on the object. 
   * @return \Drupal\forena\Frx; 
   */
  public static function instance() {
    if (static::$instance === NULL) {
      static::$instance = new static(); 
    }
    return static::$instance; 
  }
  
}