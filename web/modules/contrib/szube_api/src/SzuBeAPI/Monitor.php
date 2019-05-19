<?php

namespace Drupal\szube_api\SzuBeAPI;

/**
 * Monitor.
 */
class Monitor extends API {

  // API URL.
  const url = "https://szu.be/szu/api/Monitor/v1";

  /**
   * Execute Monitor->getMonitorsList();
   * @return Array
   */
  public function getMonitorsList() {

    // Build URL.
    $url = $this->getUrl();
    // Add parameters.
    $url .= "&action=getMonitorsList";


    return $this->execute($url);
  }

}
