<?php

namespace Drupal\bookkeeping;

use Drupal\bookkeeping\Entity\AccountInterface;
use Drupal\commerce_payment\Entity\PaymentGatewayInterface;
use Drupal\commerce_store\Entity\StoreInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Service to help set up config for bookkeeping and commerce integration.
 */
class CommerceConfig {

  /**
   * The module handler to invoke the alter hook with.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The bookkeeping account entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $accountStorage;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The bookkeeping commerce config.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Constructs the CommerceConfig service.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory) {
    $this->moduleHandler = $module_handler;
    $this->entityTypeManager = $entity_type_manager;
    $this->accountStorage = $entity_type_manager->getStorage('bookkeeping_account');
    $this->configFactory = $config_factory;
    $this->config = $config_factory->getEditable('bookkeeping.commerce');
  }

  /**
   * Initialise config on bookkeeping install.
   */
  public function onBookkeepingInstall(): void {
    if ($this->moduleHandler->moduleExists('commerce_store')) {
      $this->initStores(FALSE);
    }
    if ($this->moduleHandler->moduleExists('commerce_payment')) {
      $this->initPaymentGateways(FALSE);
    }

    $this->config->save();
  }

  /**
   * Initialise config on commerce_store install.
   */
  public function onCommerceStoreInstall(): void {
    $this->initStores();
  }

  /**
   * Initialise config on commerce_payment install.
   */
  public function onCommercePaymentInstall(): void {
    $this->initPaymentGateways();
  }

  /**
   * Ensure each store is configured.
   *
   * @param bool $save
   *   Whether to save the config changes.
   */
  public function initStores($save = TRUE): void {
    /** @var \Drupal\commerce_store\Entity\StoreInterface[] $stores */
    $stores = $this->entityTypeManager
      ->getStorage('commerce_store')
      ->loadMultiple();
    foreach ($stores as $id => $store) {
      $this->initStore($store, FALSE);
    }

    if ($save) {
      $this->config->save();
    }
  }

  /**
   * Initialise config for a commerce store.
   *
   * Adds and sets the income account for the store. Uses the default accounts
   * receivable account.
   *
   * @param \Drupal\commerce_store\Entity\StoreInterface $store
   *   The store to initilise config for.
   * @param bool $save
   *   Whether to save the config changes.
   */
  public function initStore(StoreInterface $store, $save = TRUE): void {
    $config_key = "stores.{$store->id()}";

    // Ensure tracking is enabled by default.
    $disabled = $this->config->get("{$config_key}.disabled");
    if ($disabled === NULL) {
      $this->config->set("{$config_key}.disabled", FALSE);
    }
    // If already disabled, there's nothing more to do.
    elseif ($disabled) {
      return;
    }

    // Ensure there is an income account configured.
    if (!$this->config->get("{$config_key}.income_account")) {
      // See if we already have an account based on naming convention.
      $account_id = 'commerce_store_' . $store->id();
      $account = $this->accountStorage->load($account_id);
      if (!$account) {
        $account = $this->accountStorage->create([
          'id' => $account_id,
          'label' => 'Store: ' . $store->label(),
          'type' => AccountInterface::TYPE_INCOME,
        ]);
        $account->save();
      }
      $this->config->set("{$config_key}.income_account", $account->id());
    }

    // Ensure there is an accounts receivable account configured.
    if (!$this->config->get("{$config_key}.accounts_receivable_account")) {
      $bookkeeping_config = $this->configFactory->get('bookkeeping.settings');
      $this->config->set("{$config_key}.accounts_receivable_account", $bookkeeping_config->get('accounts_receivable_account'));
    }

    if ($save) {
      $this->config->save();
    }
  }

  /**
   * Ensure each payment method is configured.
   *
   * @param bool $save
   *   Whether to save the config changes.
   */
  public function initPaymentGateways($save = TRUE): void {
    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $gateway */
    $gateway = $this->entityTypeManager
      ->getStorage('commerce_payment_gateway')
      ->loadMultiple();
    foreach ($gateway as $id => $method) {
      $this->initPaymentGateway($method, FALSE);
    }

    if ($save) {
      $this->config->save();
    }
  }

  /**
   * Initialise config for a commerce payment method.
   *
   * Adds and sets the asset account for the payment method.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentGatewayInterface $gateway
   *   The payment method to initialise config for.
   * @param bool $save
   *   Whether to save the config changes.
   */
  public function initPaymentGateway(PaymentGatewayInterface $gateway, $save = TRUE): void {
    // Ensure there is an asset account configured.
    $account_config_key = "payment_gateways.{$gateway->id()}.asset_account";
    if (!$this->config->get($account_config_key)) {
      // See if we already have an account based on naming convention.
      $account_id = 'commerce_payment_gateway_' . $gateway->id();
      $account = $this->accountStorage->load($account_id);
      if (!$account) {
        $account = $this->accountStorage->create([
          'id' => $account_id,
          'label' => 'Payment: ' . $gateway->label(),
          'type' => AccountInterface::TYPE_ASSET,
        ]);
        $account->save();
      }
      $this->config->set($account_config_key, $account->id());
    }

    if ($save) {
      $this->config->save();
    }
  }

}
