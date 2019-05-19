<?php

namespace Drupal\szube_api\SzuBeAPI;

/**
 * Site.
 */
class Test extends API {

  // API URL.
  const url = "https://szu.be/szu/api/Test/v1";

  /**
   * Execute Test->test();
   * @return Array
   */
  public function test() {

    // Build URL.
    $url = $this->getUrl();
    // Add parameters.
    $url .= "&action=test";

    return $this->execute($url);
  }

}
