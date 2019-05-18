<?php

namespace Drupal\errorlevelpermission;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

class ErrorLevelConfigOverride implements ConfigFactoryOverrideInterface {

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];
    if (in_array('system.logging', $names)) {
      $overrides['system.logging']['error_level'] = $this->errorLevel();
    }
    return $overrides;
  }

  /**
   * Get error level value depending on user permissions.
   *
   * @return string
   *   The error level.
   */
  protected function errorLevel() {
    $currentUser = \Drupal::currentUser();
    if ($currentUser->hasPermission('errorlevelpermission display verbose')) {
      return ERROR_REPORTING_DISPLAY_VERBOSE;
    }
    elseif ($currentUser->hasPermission('errorlevelpermission display all')) {
      return ERROR_REPORTING_DISPLAY_ALL;
    }
    elseif ($currentUser->hasPermission('errorlevelpermission display some')) {
      return ERROR_REPORTING_DISPLAY_SOME;
    }
    else {
      return ERROR_REPORTING_HIDE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'errorlevelpermission';
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($name) {
    $cacheableMetadata = new CacheableMetadata();
    $cacheableMetadata->addCacheContexts(['user.permissions']);
    return $cacheableMetadata;
  }

}
