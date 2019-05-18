<?php

namespace Drupal\commerce_usps;

/**
 * USPS API Service.
 *
 * @package Drupal\commerce_usps
 */
abstract class USPSRequest implements USPSRequestInterface {

  /**
   * The configuration array from a CommerceShippingMethod.
   *
   * @var array
   */
  protected $configuration;

  /**
   * {@inheritdoc}
   */
  public function setConfig(array $configuration) {
    $this->configuration = $configuration;
  }

  /**
   * Returns authentication array for a request.
   *
   * @return array
   *   An array of authentication parameters.
   */
  protected function getAuth() {
    return [
      'user_id' => $this->configuration['api_information']['user_id'],
      'password' => $this->configuration['api_information']['password'],
    ];
  }

  /**
   * Determines if the shipping method is in test method..
   *
   * @return bool
   *   Returns TRUE if we're in test mode.
   */
  protected function isTestMode() {
    return $this->configuration['api_information']['mode'] == 'test';
  }

}
