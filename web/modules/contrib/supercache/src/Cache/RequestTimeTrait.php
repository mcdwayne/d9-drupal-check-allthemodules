<?php

namespace Drupal\supercache\Cache;

trait RequestTimeTrait {

  /**
   * Current time used to validate
   * cache item expiration times.
   *
   * @var mixed
   */
  protected $requestTime;

  /**
   * Refreshes the current request time.
   *
   * Uses the global REQUEST_TIME on the first
   * call and refreshes to current time on subsequen
   * requests.
   *
   * @param int $time
   */
  public function refreshRequestTime() {
    if (empty($this->requestTime)) {
      if (defined('REQUEST_TIME')) {
        $this->requestTime = REQUEST_TIME;
        return;
      }
      if (isset($_SERVER['REQUEST_TIME_FLOAT'])) {
        $this->requestTime = round($_SERVER['REQUEST_TIME_FLOAT'], 3);
        return;
      }
    }
    $this->requestTime = round(microtime(TRUE), 3);
  }

  /**
   * Returns a 12 character length MD5.
   *
   * @param string $string
   * @return string
   */
  protected function shortMd5($string) {
    return substr(base_convert(md5($string), 16,32), 0, 12);
  }


}
