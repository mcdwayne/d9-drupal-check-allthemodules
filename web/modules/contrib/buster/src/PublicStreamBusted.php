<?php

namespace Drupal\buster;

use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Site\Settings;
use Drupal\Core\StreamWrapper\PublicStream;

/**
 * Extend the public stream wrapper and add a cache buster to the external URL.
 */
class PublicStreamBusted extends PublicStream {

  /**
   * @inheritdoc
   */
  public function getExternalUrl() {
    $file_url = parent::getExternalUrl();

    if ($this->isCacheBusterRequired($this->uri)) {
      $token_data = [
        $this->uri,
      ];
      if ((file_exists($this->uri)) && ($file_hash = sha1_file($this->uri))) {
        $token_data[] = $file_hash;
      }
      else {
        $token_data[] = \Drupal::time()->getRequestTime();
      }

      $token_query = [
        '_buster' => $this->getBusterToken(implode(':', $token_data)),
      ];

      $file_url .= (strpos($file_url, '?') !== FALSE ? '&' : '?') . UrlHelper::buildQuery($token_query);
    }

    return $file_url;
  }

  /**
   * Determine if the specified URL requires cache busting tokens.
   *
   * @param $uri
   *
   * @return bool
   */
  protected function isCacheBusterRequired($uri) {
    return strpos($this->uri, 'public://css/') === FALSE  && strpos($this->uri, 'public://js/') === FALSE;
  }

  /**
   * Compute a token for cache busting a URL.
   *
   * @param string $data
   *   The data to hash and turn into a URL token.
   *
   * @return string
   */
  protected function getBusterToken($data) {
    return substr(Crypt::hmacBase64($data, $this->getPrivateKey() . $this->getHashSalt()), 0, 8);
  }

  /**
   * Gets the Drupal private key.
   *
   * @return string
   *   The Drupal private key.
   */
  protected function getPrivateKey() {
    return \Drupal::service('private_key')->get();
  }

  /**
   * Gets a salt useful for hardening against SQL injection.
   *
   * @return string
   *   A salt based on information in settings.php, not in the database.
   *
   * @throws \RuntimeException
   */
  protected function getHashSalt() {
    return Settings::getHashSalt();
  }
}
