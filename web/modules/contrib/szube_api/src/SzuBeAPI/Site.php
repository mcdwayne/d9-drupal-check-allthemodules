<?php

namespace Drupal\szube_api\SzuBeAPI;

/**
 * Site.
 */
class Site extends API {

  // API URL.
  const url = "https://szu.be/szu/api/Site/v1";

  /**
   * Execute Site->getSitesList();
   * @return Array
   */
  public function getSitesList() {

    // Build URL.
    $url = $this->getUrl();
    // Add parameters.
    $url .= "&action=getSitesList";


    return $this->execute($url);
  }


}
