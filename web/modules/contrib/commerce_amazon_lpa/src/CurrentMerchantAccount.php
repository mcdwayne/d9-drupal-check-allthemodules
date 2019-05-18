<?php

namespace Drupal\commerce_amazon_lpa;

use Drupal\commerce_store\CurrentStoreInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Class for the current merchant account.
 */
class CurrentMerchantAccount implements CurrentMerchantAccountInterface {

  /**
   * The current store.
   *
   * @var \Drupal\commerce_store\CurrentStoreInterface
   */
  protected $currentStore;

  /**
   * The Amazon Pay settings.
   *
   * @var array|\Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig|mixed|null
   */
  protected $amazonPaySettings;

  /**
   * Constructs a new CurrentMerchantAccount object.
   *
   * @param \Drupal\commerce_store\CurrentStoreInterface $current_store
   *   The current store.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(CurrentStoreInterface $current_store, ConfigFactoryInterface $config_factory) {
    $this->currentStore = $current_store;
    $this->amazonPaySettings = $config_factory->get('commerce_amazon_lpa.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function resolve() {
    $merchant_information = $this->amazonPaySettings->get('merchant_information');
    $current_store = $this->currentStore->getStore();
    if (!empty($current_store) && isset($merchant_information[$current_store->uuid()])) {
      return $merchant_information[$current_store->uuid()];
    }
  }

}
