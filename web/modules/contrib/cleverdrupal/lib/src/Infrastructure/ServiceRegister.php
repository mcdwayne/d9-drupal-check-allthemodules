<?php

namespace CleverReach\Infrastructure;

/**
 *
 */
class ServiceRegister {

  /**
   * Service register instance.
   *
   * @var ServiceRegister
   */
  private static $instance;

  /**
   * Array of registered services.
   *
   * @var array
   */
  private $services;

  /**
   *
   */
  public function __construct($services = []) {
    if (!empty($services)) {
      foreach ($services as $type => $service) {
        $this->register($type, $service);
      }
    }

    self::$instance = $this;
  }

  /**
   * Getting service register instance.
   *
   * @return ServiceRegister
   */
  public static function getInstance() {
    if (empty(self::$instance)) {
      self::$instance = new ServiceRegister();
    }

    return self::$instance;
  }

  /**
   * Gets service.
   *
   * @param $type
   *
   * @return mixed
   *
   * @throws \InvalidArgumentException
   */
  public static function getService($type) {
    return self::getInstance()->get($type);
  }

  /**
   * Registers service with delegate as second parameter which represents function for creating new service instance.
   *
   * @param $type
   * @param $delegate
   */
  public static function registerService($type, $delegate) {
    self::getInstance()->register($type, $delegate);
  }

  /**
   * Register service class.
   *
   * @param $type
   * @param $delegate
   */
  private function register($type, $delegate) {
    if (!empty($this->services[$type])) {
      throw new \InvalidArgumentException("$type is already defined.");
    }

    if (!is_callable($delegate)) {
      throw new \InvalidArgumentException("$type delegate is not callable.");
    }

    $this->services[$type] = $delegate;
  }

  /**
   * Getting service instance.
   *
   * @param $type
   *
   * @return mixed
   *
   * @throws \InvalidArgumentException
   */
  private function get($type) {
    if (empty($this->services[$type])) {
      throw new \InvalidArgumentException("$type is not defined.");
    }

    return call_user_func($this->services[$type]);
  }

}
