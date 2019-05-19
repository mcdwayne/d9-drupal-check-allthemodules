<?php

namespace Drupal\szube_api\SzuBeAPI;

/**
 * Review.
 */
class Review extends API {

  // API URL.
  const url = "https://szu.be/szu/api/Review/v1";

  /**
   * Execute Review->getSitesList();
   * @return Array
   */
  public function getSitesList() {

    // Build URL.
    $url = $this->getUrl();
    // Add parameters.
    $url .= "&action=getMonitorsList";


    return $this->execute($url);
  }

  /**
   * Execute Review->getReviewsList();
   * @param $siteId
   * @param int $from
   * @param int $limit
   * @param string $status
   * @return Array
   */
  public function getReviewsList($siteId, $from = 0, $limit = 10, $status = '') {

    // Build URL.
    $url = $this->getUrl();
    // Add parameters.
    $url .= "&action=getReviewsList&siteId=$siteId";
    if ($from) {
      $url .= "&from=$from";
    }
    if ($limit) {
      $url .= "&limit=$limit";
    }
    if ($status) {
      $url .= "&status=$status";
    }

    return $this->execute($url);
  }

}
