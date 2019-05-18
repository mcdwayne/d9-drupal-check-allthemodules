<?php

namespace Drupal\env_dependencies;

use Drupal\Core\Config\ImmutableConfig;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class EnvDependenciesEvent.
 *
 * @package Drupal\env_dependencies
 */
class EnvDependenciesEvent extends Event {
  const BEFORE_IMPORT_CONFIG = 'event.submit';
  const BEFORE_ENABLE_DEPENDENCIES = 'event.submit';
  const AFTER_ENABLE_DEPENDENCIES = 'event.submit';

  protected $config;
  protected $environment;

  /**
   * EnvDependenciesEvent constructor.
   *
   * @param string $environment
   *   Environment.
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   Config object.
   */
  public function __construct($environment, ImmutableConfig $config) {
    $this->environment = $environment;
    $this->config = $config;
  }

  /**
   * Get environment name.
   *
   * @return string
   *   return environment.
   */
  public function getEnvironment() {
    return $this->environment;
  }

  /**
   * Get config.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   return config object.
   */
  public function getConfig() {
    return $this->config;
  }

}
