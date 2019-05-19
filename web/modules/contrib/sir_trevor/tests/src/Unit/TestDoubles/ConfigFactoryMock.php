<?php

namespace Drupal\Tests\sir_trevor\Unit\TestDoubles;

use Drupal\Core\Config\ImmutableConfig;

class ConfigFactoryMock extends ConfigFactoryDummy {
  /** @var ImmutableConfig[] */
  private $configs = [];

  /**
   * @param string $name
   * @param \Drupal\Core\Config\ImmutableConfig $config
   */
  public function set($name, ImmutableConfig $config) {
    $this->configs[$name] = $config;
  }

  /**
   * @param string $name
   * @return bool
   */
  public function has($name) {
    return isset($this->configs[$name]);
  }

  /**
   * {@inheritdoc}
   */
  public function get($name) {
    return $this->configs[$name];
  }
}
