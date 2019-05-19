<?php

namespace Drupal\Tests\sir_trevor\Unit\TestDoubles;

use Drupal\Core\Config\ConfigFactoryOverrideInterface;

class ConfigFactorySpy extends ConfigFactoryMock {
  private $calledMethods = [];

  /**
   * @param string $methodName
   *   The methodname to register and call.
   * @param array $arguments
   *   The arguments used when the method is called.
   * @return mixed
   *   The value returned by the method call.
   */
  private function registerMethodCall($methodName, $arguments = []) {
    $this->calledMethods[] = [
      'name' => $methodName,
      'arguments' => $arguments,
    ];

    return call_user_func_array(array($this, "parent::{$methodName}"), $arguments);
  }

  /**
   * @return array[]
   */
  public function getCalledMethods() {
    return $this->calledMethods;
  }

  /**
   * {@inheritdoc}
   */
  public function get($name) {
    return $this->registerMethodCall(__FUNCTION__, func_get_args());
  }

  /**
   * {@inheritdoc}
   */
  public function getEditable($name) {
    return $this->registerMethodCall(__FUNCTION__, func_get_args());
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultiple(array $names) {
    return $this->registerMethodCall(__FUNCTION__, func_get_args());
  }

  /**
   * {@inheritdoc}
   */
  public function reset($name = NULL) {
    return $this->registerMethodCall(__FUNCTION__, func_get_args());
  }

  /**
   * {@inheritdoc}
   */
  public function rename($old_name, $new_name) {
    return $this->registerMethodCall(__FUNCTION__, func_get_args());
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheKeys() {
    return $this->registerMethodCall(__FUNCTION__, func_get_args());
  }

  /**
   * {@inheritdoc}
   */
  public function clearStaticCache() {
    return $this->registerMethodCall(__FUNCTION__, func_get_args());
  }

  /**
   * {@inheritdoc}
   */
  public function listAll($prefix = '') {
    return $this->registerMethodCall(__FUNCTION__, func_get_args());
  }

  /**
   * {@inheritdoc}
   */
  public function addOverride(ConfigFactoryOverrideInterface $config_factory_override) {
    $this->registerMethodCall(__FUNCTION__, func_get_args());
  }
}
