<?php

/**
 * @file
 * FirebaseConfigManager.
 */

namespace Drupal\tmgmt_smartling\Smartling\ConfigManager;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\tmgmt_smartling\Smartling\SmartlingApiWrapper;

/**
 * Class FirebaseConfigManager.
 */
class FirebaseConfigManager extends SmartlingConfigManager {

  /**
   * @var \Drupal\tmgmt_smartling\Smartling\SmartlingApiWrapper
   */
  protected $apiWrapper;

  /**
   * @var \Drupal\Core\Cache\CacheBackendInterface $defaultCache
   */
  protected $defaultCache;

  /**
   * @param \Drupal\tmgmt_smartling\Smartling\SmartlingApiWrapper $apiWrapper
   */
  public function setSmartlingApiWrapper(SmartlingApiWrapper $apiWrapper) {
    $this->apiWrapper = $apiWrapper;
  }

  /**
   * @param \Drupal\Core\Cache\CacheBackendInterface $defaultCache
   */
  public function setDefaultCache(CacheBackendInterface $defaultCache) {
    $this->defaultCache = $defaultCache;
  }

  /**
   * Returns array of available smartling providers with enabled notifications.
   *
   * @return array
   */
  public function getAvailableConfigs() {
    $firebaseConfigs = [];
    $configs = parent::getAvailableConfigs();

    foreach ($configs as $config) {
      $provider_settings = $config->get('settings');

      if (empty($provider_settings['enable_notifications'])) {
        continue;
      }

      $cachedData = $this->defaultCache->get("tmgmt_smartling.firebase_config.{$provider_settings["project_id"]}");

      if (empty($cachedData)) {
        try {
          $this->apiWrapper->setSettings($provider_settings);
          $project_details = $this->apiWrapper
            ->getApi("project")
            ->getProjectDetails();

          $data = $this->apiWrapper
            ->getApi("progress")
            ->getToken($project_details["accountUid"]);

          $data["accountUid"] = $project_details["accountUid"];
          $data["projectId"] = $provider_settings["project_id"];

          $firebaseConfigs[] = $data;
          $this->defaultCache->set(
            "tmgmt_smartling.firebase_config.{$provider_settings["project_id"]}",
            $data,
            time() + 3600,
            ["tmgmt_smartling:firebase_config:{$provider_settings["project_id"]}"]
          );
        }
        catch (\Exception $e) {
          // Empty settings, can't fetch project details.
        }
      }
      else {
        $firebaseConfigs[] = $cachedData->data;
      }
    }

    return $firebaseConfigs;
  }

}
