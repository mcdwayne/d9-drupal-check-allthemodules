<?php

namespace Drupal\stripe_api;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\key\KeyRepositoryInterface;
use Psr\Log\LoggerInterface;
use Stripe\Stripe;

/**
 * Class StripeApiService.
 *
 * @package Drupal\stripe_api
 */
class StripeApiService {

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /** @var \Drupal\key\KeyRepositoryInterface */
  protected $key;

  /**
   * Constructor.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, LoggerInterface $logger, KeyRepositoryInterface $key) {
    $this->config = $config_factory->get('stripe_api.settings');
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
    $this->key = $key;
    Stripe::setApiKey($this->getApiKey());
    $this->overrideApiVersion();
  }

  /**
   *
   */
  public function getMode() {
    $mode = $this->config->get('mode');

    if (!$mode) {
      $mode = 'test';
    }

    return $mode;
  }

  /**
   *
   */
  public function getApiKey() {
    $config_key = $this->getMode() . '_secret_key';
    $key_id = $this->config->get($config_key);
    if ($key_id) {
      $key_entity = $this->key->getKey($key_id);
      if ($key_entity) {
        return $key_entity->getKeyValue();
      }

    }

    return NULL;
  }

  /**
   *
   */
  public function getPubKey() {
    $config_key = $this->getMode() . '_public_key';
    $key_id = $this->config->get($config_key);
    if ($key_id) {
      $key_entity = $this->key->getKey($key_id);
      if ($key_entity) {
        return $key_entity->getKeyValue();
      }
    }

    return NULL;
  }

  /**
   * Overrides API version.
   */
  public function overrideApiVersion() {
    if ($this->config->get('api_version') === 'custom') {
      Stripe::setApiVersion($this->config->get('api_version_custom'));
    }
  }

  /**
   * Makes a call to the Stripe API.
   *
   * @param string $object
   *   Stripe object. Can be a Charge, Refund, Customer, Subscription, Card,
   *   Plan, Coupon, Discount, Invoice, InvoiceItem, Dispute, Transfer,
   *   TransferReversal, Recipient, BankAccount, ApplicationFee, FeeRefund,
   *   Account, Balance, Event, Token, BitcoinReceiver, FileUpload.
   * @param string $method
   *   Stripe object method. Common operations include retrieve, all, create.
   * @param ...
   *   Additional params to pass to the method. Can be an array, string.
   *
   * @return \Stripe\ApiResource|string|null
   *   Returns the ApiResource or NULL on error or string which contains called
   *   class if method not exist.
   */
  public function call($object, $method) {
    $object = ucfirst($object);
    $class = '\\Stripe\\' . $object;
    $args = func_get_args();

    // Remove $object and $method from the arguments.
    unset($args[0], $args[1]);
    if ($method) {
      try {
        return call_user_func_array([$class, $method], $args);
      }
      catch (\Throwable $e) {
        $this->logger->error('Error: @error <br /> @args', [
          '@args' => Json::encode([
            'object' => $object,
            'method' => $method,
            'args' => $args,
          ]),
          '@error' => $e->getMessage(),
        ]);
        return NULL;
      }
    }
    return $class;
  }

}
