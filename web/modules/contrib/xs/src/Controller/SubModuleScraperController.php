<?php

namespace Drupal\xpath_scraper\Controller;

/**
 * A service for checking if scraper configuration available at sub module.
 */
class SubModuleScraperController implements SubModuleScraperInterface {

  /**
   * {@inheritdoc}
   */
  public function moduleConfigExists($module) {
    // Check if configration class is exists at sub module folder.
    $config_class = drupal_get_path('module', $module) . "/src/Controller/ScraperConfigController.php";
    if (file_exists($config_class)) {
      return TRUE;
    }
    return FALSE;
  }

}
