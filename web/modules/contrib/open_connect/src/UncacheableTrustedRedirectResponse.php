<?php

namespace Drupal\open_connect;

use Drupal\Component\HttpFoundation\SecuredRedirectResponse;

/**
 * Provides an uncacheable redirect response which contains trusted URLs.
 *
 * Use this class in case you know that you want to redirect to an external URL.
 */
class UncacheableTrustedRedirectResponse extends SecuredRedirectResponse {

  /**
   * A list of trusted URLs, which are safe to redirect to.
   *
   * @var string[]
   */
  protected $trustedUrls = [];

  /**
   * {@inheritdoc}
   */
  public function __construct($url, $status = 302, $headers = []) {
    $this->trustedUrls[$url] = TRUE;
    parent::__construct($url, $status, $headers);
  }

  /**
   * Sets the target URL to a trusted URL.
   *
   * @param string $url
   *   A trusted URL.
   *
   * @return $this
   */
  public function setTrustedTargetUrl($url) {
    $this->trustedUrls[$url] = TRUE;
    return $this->setTargetUrl($url);
  }

  /**
   * {@inheritdoc}
   */
  protected function isSafe($url) {
    return !empty($this->trustedUrls[$url]);
  }

}
