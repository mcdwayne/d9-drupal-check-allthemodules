<?php

/**
 * @file
 * FirebaseConfigManager.
 */

namespace Drupal\tmgmt_smartling\Smartling\ConfigManager;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Class SmartlingConfigManager.
 */
class SmartlingConfigManager {

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * SmartlingConfigManager constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->configFactory = $configFactory;
  }

  /**
   * Returns array of available smartling providers.
   *
   * @return array
   */
  public function getAvailableConfigs() {
    $configs = [];
    $translator_ids = $this->configFactory->listAll('tmgmt.translator');

    foreach ($translator_ids as $id) {
      $config = $this->configFactory->get($id);

      if ($config->get('plugin') === 'smartling') {
        $configs[] = $config;
      }
    }

    return $configs;
  }

  /**
   * Returns smartling provider config by project id.
   *
   * @param $projectId
   * @return Config|NULL
   */
  public function getConfigByProjectId($projectId) {
    $configs = $this->getAvailableConfigs();
    $result = NULL;

    foreach ($configs as $config) {
      $provider_settings = $config->get('settings');

      if (
        !empty($provider_settings["project_id"]) &&
        $provider_settings["project_id"] == $projectId
      ) {
        $result = $config;

        break;
      }
    }

    return $result;
  }

}
