<?php

namespace Drupal\braintree_api;

use Braintree\Gateway;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\key\KeyRepository;

/**
 * Class BraintreeApiService.
 */
class BraintreeApiService implements BraintreeApiServiceInterface {

  /**
   * Drupal\Core\Config\ImmutableConfig definition.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   *  The Braintree API Configuration settings.
   */
  protected $config;

  /**
   * Drupal\key\KeyRepository definition.
   *
   * @var \Drupal\key\KeyRepository
   */
  protected $keyRepository;

  /**
   * The Braintree Gateway.
   *
   * @var \Braintree\Gateway
   */
  protected $gateway;

  /**
   * Constructs a new BraintreeApiService object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, KeyRepository $key_repository) {
    $this->config = $config_factory->get('braintree_api.settings');
    $this->keyRepository = $key_repository;
    $this->gateway = new Gateway([
      'environment' => $this->getEnvironment(),
      'merchantId' => $this->getMerchantId(),
      'publicKey' => $this->getPublicKey(),
      'privateKey' => $this->getPrivateKey(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getGateway() {
    return $this->gateway;
  }

  /**
   * {@inheritdoc}
   */
  public function getEnvironment() {
    $mode = $this->config->get('environment');
    if (!$mode) {
      return BraintreeApiService::ENVIRONMENT_SANDBOX;
    }
    return $mode;
  }

  /**
   * {@inheritdoc}
   */
  public function getMerchantId() {
    return $this->config->get($this->getEnvironment() . '_merchant_id');
  }

  /**
   * {@inheritdoc}
   */
  public function getPrivateKey() {
    return $this->getKeyValue($this->getEnvironment() . '_private_key');
  }

  /**
   * {@inheritdoc}
   */
  public function getPublicKey() {
    return $this->getKeyValue($this->getEnvironment() . '_public_key');
  }

  /**
   * Get the value held in the key referred to by the configuration key ID.
   *
   * @param string $config_key
   *   The form value key in the braintree_api_admin_form.
   *
   * @return string|null
   *   The value held in the key.
   */
  private function getKeyValue($config_key) {
    $key_id = $this->config->get($config_key);
    if ($key_id) {
      $key_entity = $this->keyRepository->getKey($key_id);
      if ($key_entity) {
        return $key_entity->getKeyValue();
      }
    }
    return NULL;
  }

}
