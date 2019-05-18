<?php

namespace Drupal\commerce_klaviyo\Util;

/**
 * Allows setter injection and simple usage of the service.
 *
 * @package Drupal\commerce_klaviyo\Util
 */
trait KlaviyoRequestTrait {

  /**
   * The KlaviyoRequest service.
   *
   * @var \Drupal\commerce_klaviyo\Util\KlaviyoRequest
   */
  protected $klaviyoRequest;

  /**
   * Sets the KlaviyoRequest service.
   *
   * @param \Drupal\commerce_klaviyo\Util\KlaviyoRequest $klaviyo_request
   *   The KlaviyoRequest service.
   *
   * @return $this
   */
  public function setKlaviyoRequest(KlaviyoRequest $klaviyo_request) {
    $this->klaviyoRequest = $klaviyo_request;
    return $this;
  }

  /**
   * Gets the KlaviyoRequest service.
   *
   * @return \Drupal\commerce_klaviyo\Util\KlaviyoRequest
   *   The KlaviyoRequest service.
   */
  public function getKlaviyoRequest() {
    if (empty($this->klaviyoRequest)) {
      $this->klaviyoRequest = \Drupal::service('commerce_klaviyo.klaviyo_request');
    }
    return $this->klaviyoRequest;
  }

}
