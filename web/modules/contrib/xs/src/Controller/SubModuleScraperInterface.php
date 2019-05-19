<?php

namespace Drupal\xpath_scraper\Controller;

/**
 * An interface defining a sub module scraper.
 */
interface SubModuleScraperInterface {

  /**
   * Import sub module scraper configuration from a given module.
   *
   * @param string $module
   *   The module to create the sub module scraper.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   Scrape module by it's configuration.
   */
  public function moduleConfigExists($module);

}
