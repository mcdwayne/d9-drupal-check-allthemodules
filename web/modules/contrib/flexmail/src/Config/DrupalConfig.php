<?php

/**
 * @todo Write file documentation.
 */

namespace Drupal\flexmail\Config;

use Finlet\flexmail\Config\ConfigInterface;

class DrupalConfig implements ConfigInterface {

  private $container = array();

  public function __construct(\Drupal\Core\Config\Config $config) {
    $this->set('wsdl', $config->get('wsdl'));
    $this->set('service', $config->get('service'));
    $this->set('user_id', $config->get('user_id'));
    $this->set('user_token', $config->get('user_token'));
    $this->set('debug_mode', $config->get('debug_mode'));
  }

  public function get($key) {
    return $this->set($key);
  }

  public function set($key, $value = NULL) {
    if ($value !== NULL) {
      $this->container[$key] = $value;
    }

    return $this->container[$key];
  }
}

?>