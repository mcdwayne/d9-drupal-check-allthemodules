<?php

namespace Drupal\developer_module\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Create class for service to use multiple times.
 */
class DeveloperModuleGetMethods extends ControllerBase {

  /**
   * Get Theme Debug.
   */
  public function getEnableThemeDebug() {

    $getThemeDebug = $this->config('developer_module.formsettings')->get('enable_theme_debug');
    return $getThemeDebug != NULL ? $getThemeDebug : 0;
  }

  /**
   * Get Disable Cache During Development Mode.
   */
  public function getDisableCacheDuringDevelopment() {

    $getDisableCache = $this->config('developer_module.formsettings')->get('disable_cache_during_development');
    return $getDisableCache != NULL ? $getDisableCache : 0;
  }

}
