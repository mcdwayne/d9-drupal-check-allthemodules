<?php

namespace Drupal\formfactorykits\Kits\Traits;

/**
 * Class ConfigFactoryTrait
 *
 * @package Drupal\formfactorykits\Kits\Traits
 */
trait ConfigFactoryTrait {
  /**
   * @return \Drupal\Core\Config\ConfigFactoryInterface
   */
  public function getConfigFactoryService() {
    /** @var \Drupal\Core\Config\ConfigFactoryInterface $service */
    static $service;
    if (NULL === $service) {
      $service = $this->kitsService->getContainer()
        ->get('config.factory');
    }
    return $service;
  }

  /**
   * @param string $name
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   */
  public function getConfig($name) {
    return $this->getConfigFactoryService()->get($name);
  }

  /**
   * @param string $name
   *
   * @return array
   */
  public function getConfigData($name) {
    $config = $this->getConfig($name);
    if (!$config) {
      return [];
    }
    return $config->getRawData();
  }
}
