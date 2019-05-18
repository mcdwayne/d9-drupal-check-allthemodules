<?php

namespace Drupal\devel_mode;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;

/**
 * Class ConfigOverride.
 *
 * @package Drupal\devel_mode
 */
class ConfigOverride implements ConfigFactoryOverrideInterface, ConfigOverrideInterface {

  protected $configProvider;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigProvider $config) {
    $this->configProvider = $config;
  }

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = array();
    if (in_array('system.logging', $names)) {
      $overrides['system.logging'] = [
        'error_level' => 'verbose',
      ];
    }
    if (in_array('system.performance', $names)) {
      $configs = $this->configProvider->getConfigs();
      $overrides['system.performance'] = [
        'cache' => ['page' => ['max_age' => 0]],
      ];
      if ($configs['disable_preprocess_js']) {
        $overrides['system.performance'] += [
          'js' => ['preprocess' => FALSE],
        ];
      }
      if ($configs['disable_preprocess_css']) {
        $overrides['system.performance'] += [
          'css' => ['preprocess' => FALSE],
        ];
      }
    }
    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'DevelMode';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($name) {
    return new CacheableMetadata();
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

}
