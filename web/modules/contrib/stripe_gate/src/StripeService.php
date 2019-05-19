<?php

namespace Drupal\stripe;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\Client;
use Stripe\Stripe;

/**
 * Class that define the service that consumes the Stripe API.
 */
class StripeService implements ContainerFactoryPluginInterface {

  /**
   * Stores the products.
   *
   * @var \Drupal\stripe\StripeService
   */
  protected $products;

  /**
   * Stores the plans.
   *
   * @var \Drupal\stripe\StripeService
   */
  protected $plans;

  /**
   * Stores the customer.
   *
   * @var \Drupal\stripe\StripeService
   */
  protected $customer;

  /**
   * Stores the configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Guzzle Http Client.
   *
   * @var GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * When the service is created, set a value for the products variable.
   */
  public function __construct(ConfigFactoryInterface $config_factory, Client $http_client) {
    $this->products = [];
    $this->configFactory = $config_factory;
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('config.factory'),
      $container->get('http_client')
    );
  }

  /**
   * Return Publishable Key.
   */
  public function getPKey() {
    // Loading the configuration.
    $config = $this->configFactory->get('stripe.settings');

    // Setting the secret key.
    $pKey = $config->get('stripe.use_test') ?
      $config->get('stripe.pk_test') :
      $config->get('stripe.pk_live');

    return ['value' => $pKey];
  }

  /**
   * Return the products.
   */
  public function getProducts() {
    // Loading the configuration.
    $config = $this->configFactory->get('stripe.settings');

    // Setting the secret key.
    $secretKey = $config->get('stripe.use_test') ?
      $config->get('stripe.sk_test') :
      $config->get('stripe.sk_live');

    Stripe::setApiKey($secretKey);

    // Getting all the products.
    $this->products = \Stripe\Product::all();

    return $this->products->data;
  }

  /**
   * Create new products.
   */
  public function createServiceProduct($name, $unit_label, $statement_descriptor) {
    // Loading the configuration.
    $config = $this->configFactory->get('stripe.settings');

    // Setting the secret key.
    $secretKey = $config->get('stripe.use_test') ?
      $config->get('stripe.sk_test') :
      $config->get('stripe.sk_live');

    Stripe::setApiKey($secretKey);

    // Creating the product.
    \Stripe\Product::create([
      'name' => $name,
      'type' => 'service',
      'statement_descriptor' => $statement_descriptor,
      'unit_label' => $unit_label,
    ]);
  }

  /**
   * Return the plans.
   */
  public function getPlans() {
    // Loading the configuration.
    $config = $this->configFactory->get('stripe.settings');

    // Setting the secret key.
    $secretKey = $config->get('stripe.use_test') ?
      $config->get('stripe.sk_test') :
      $config->get('stripe.sk_live');

    Stripe::setApiKey($secretKey);

    // Getting all the plans.
    $this->plans = \Stripe\Plan::all();

    return $this->plans->data;
  }

  /**
   * Create new plans.
   */
  public function createPlan($nickname, $product, $currency, $interval, $price, $trial_days) {
    // Loading the configuration.
    $config = $this->configFactory->get('stripe.settings');

    // Setting the secret key.
    $secretKey = $config->get('stripe.use_test') ?
      $config->get('stripe.sk_test') :
      $config->get('stripe.sk_live');

    Stripe::setApiKey($secretKey);

    // Creating the product.
    \Stripe\Plan::create([
      'product' => $product,
      'currency' => $currency,
      'interval' => $interval,
      'interval_count' => 1,
      'usage_type' => 'licensed',
      'billing_scheme' => 'per_unit',
      'nickname' => $nickname,
      'trial_period_days' => $trial_days,
      'amount' => (int) $price * 100,
    ]);
  }

  /**
   * Create new customers.
   */
  public function createCustomer($token_info) {
    // Loading the configuration.
    $config = $this->configFactory->get('stripe.settings');

    // Setting the secret key.
    $secretKey = $config->get('stripe.use_test') ?
      $config->get('stripe.sk_test') :
      $config->get('stripe.sk_live');

    Stripe::setApiKey($secretKey);

    // Create the customer on Stripe.
    $customer = \Stripe\Customer::create([
      'email' => $token_info['email'],
      'source' => $token_info['id'],
    ]);

    return $customer;
  }

  /**
   * Return the plans.
   */
  public function getCustomer($customer_info) {
    // Loading the configuration.
    $config = $this->configFactory->get('stripe.settings');

    // Setting the secret key.
    $secretKey = $config->get('stripe.use_test') ?
      $config->get('stripe.sk_test') :
      $config->get('stripe.sk_live');

    Stripe::setApiKey($secretKey);

    // Getting the customer.
    $this->customer = \Stripe\Customer::retrieve($customer_info['id']);

    return $this->customer;
  }

  /**
   * Create new subscriptions.
   */
  public function createSubscription($customer) {
    // Loading the configuration.
    $config = $this->configFactory->get('stripe.settings');

    // Setting the secret key.
    $secretKey = $config->get('stripe.use_test') ?
      $config->get('stripe.sk_test') :
      $config->get('stripe.sk_live');

    Stripe::setApiKey($secretKey);

    // Create the subscription on Stripe.
    $subscription = \Stripe\Subscription::create([
      'customer' => $customer['customerId'],
      'items' => [
        ['plan' => $customer['plan']['id']]
      ],
    ]);

    return $subscription;
  }

  /**
   * Create new subscriptions.
   */
  public function cancelSubscription($subscription_info) {
    // Loading the configuration.
    $config = $this->configFactory->get('stripe.settings');

    // Setting the secret key.
    $secretKey = $config->get('stripe.use_test') ?
      $config->get('stripe.sk_test') :
      $config->get('stripe.sk_live');

    Stripe::setApiKey($secretKey);

    // Cancel the subscription on Stripe.
    $subscription = \Stripe\Subscription::retrieve($subscription_info['id']);
    $subscription->cancel();

    return $subscription;
  }

  /**
   * Create new subscriptions.
   */
  public function cancelSubscriptionDue($subscription_info) {
    // Loading the configuration.
    $config = $this->configFactory->get('stripe.settings');

    // Setting the secret key.
    $secretKey = $config->get('stripe.use_test') ?
      $config->get('stripe.sk_test') :
      $config->get('stripe.sk_live');

    Stripe::setApiKey($secretKey);

    // Cancel the subscription on Stripe.
    $subscription = \Stripe\Subscription::retrieve($subscription_info['id']);
    $subscription->cancel_at_period_end = TRUE;
    $subscription->save();

    return $subscription;
  }

}
