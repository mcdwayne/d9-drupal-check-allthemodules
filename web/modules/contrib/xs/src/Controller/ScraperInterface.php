<?php

namespace Drupal\xpath_scraper\Controller;

/**
 * An interface defining a website scraper.
 */
interface ScraperInterface {

  /**
   * Scrape website data.
   *
   * @return void
   */
  public function scrape();

}
