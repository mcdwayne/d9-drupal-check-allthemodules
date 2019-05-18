<?php

namespace Drupal\commerce_avatax;

use Drupal\address\AddressInterface;
use Drupal\commerce_avatax\Resolver\ChainTaxCodeResolverInterface;
use Drupal\commerce_order\AdjustmentTypeManager;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\commerce_tax\Event\CustomerProfileEvent;
use Drupal\commerce_tax\Event\TaxEvents;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Variable;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class AvataxLib {

  /**
   * The adjustment type manager.
   *
   * @var \Drupal\commerce_order\AdjustmentTypeManager
   */
  protected $adjustmentTypeManager;

  /**
   * The chain tax code resolver.
   *
   * @var \Drupal\commerce_avatax\Resolver\ChainTaxCodeResolverInterface
   */
  protected $chainTaxCodeResolver;

  /**
   * The client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * The Avatax configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Constructs a new AvataxLib object.
   *
   * @param \Drupal\commerce_order\AdjustmentTypeManager $adjustment_type_manager
   *   The adjustment type manager.
   * @param \Drupal\commerce_avatax\Resolver\ChainTaxCodeResolverInterface $chain_tax_code_resolver
   *   The chain tax code resolver.
   * @param \Drupal\commerce_avatax\ClientFactory $client_factory
   *   The client.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   */
  public function __construct(AdjustmentTypeManager $adjustment_type_manager, ChainTaxCodeResolverInterface $chain_tax_code_resolver, ClientFactory $client_factory, ConfigFactoryInterface $config_factory, EventDispatcherInterface $event_dispatcher, LoggerChannelFactoryInterface $logger_factory, ModuleHandlerInterface $module_handler, CacheBackendInterface $cache_backend) {
    $this->adjustmentTypeManager = $adjustment_type_manager;
    $this->chainTaxCodeResolver = $chain_tax_code_resolver;
    $this->config = $config_factory->get('commerce_avatax.settings');
    $this->client = $client_factory->createInstance($this->config->get());
    $this->eventDispatcher = $event_dispatcher;
    $this->logger = $logger_factory->get('commerce_avatax');
    $this->moduleHandler = $module_handler;
    $this->cache = $cache_backend;
  }

  /**
   * Create a new transaction (/api/v2/transactions/create).
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   * @param string $type
   *   The transactions type (e.g SalesOrder|SalesInvoice).
   * @return array
   */
  public function transactionsCreate(OrderInterface $order, $type = 'SalesOrder') {
    $request_body = $this->prepareTransactionsCreate($order, $type);

    // Do not go further unless there have been lines added.
    if (empty($request_body['lines'])) {
      return [];
    }
    $cid = 'transactions_create:' . $order->id();
    // Check if the response was cached, and return it in case the request
    // about to be performed is different than the one in cache.
    if ($cached = $this->cache->get($cid)) {
      $cached_data = $cached->data;

      if (!empty($cached_data['response']) && isset($cached_data['request'])) {
        // The comparison would always fail if we wouldn't artificially override
        // the date here.
        $cached_data['request']['date'] = $request_body['date'];

        if ($cached_data['request'] == $request_body) {
          return $cached_data['response'];
        }
      }
    }

    $response_body = $this->doRequest('POST', 'api/v2/transactions/create', ['json' => $request_body]);
    if (!empty($response_body)) {
      $this->moduleHandler->alter('commerce_avatax_order_response', $response_body, $order);
      // Cache the request + the response for 24 hours.
      $expire = time() + (60 * 60 * 24);
      $this->cache->set($cid, ['request' => $request_body, 'response' => $response_body], $expire);
    }
    return $response_body;
  }

  /**
   * Void a transaction.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   */
  public function transactionsVoid(OrderInterface $order) {
    $store = $order->getStore();
    // Attempt to get company code for specific store, otherwise, fallback to
    // the company code configured in the settings.
    if ($store->get('avatax_company_code')->isEmpty()) {
      $company_code = $this->config->get('company_code');
    }
    else {
      $company_code = $store->get('avatax_company_code')->value;
    }
    $transaction_code = 'DC-' . $order->uuid();
    return $this->doRequest('POST', "api/v2/companies/$company_code/transactions/$transaction_code/void", [
      'json' => [
        'code' => 'DocVoided',
      ],
    ]);
  }

  /**
   * Prepare the transaction request body.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   * @param string $type
   *   The transactions type (e.g SalesOrder|SalesInvoice).
   * @return array
   */
  public function prepareTransactionsCreate(OrderInterface $order, $type = 'SalesOrder') {
    $store = $order->getStore();
    // Attempt to get company code for specific store, otherwise, fallback to
    // the company code configured in the settings.
    if ($store->get('avatax_company_code')->isEmpty()) {
      $company_code = $this->config->get('company_code');
    }
    else {
      $company_code = $store->get('avatax_company_code')->value;
    }
    $date = new DrupalDateTime();
    // Gather all the adjustment types.
    $adjustment_types = array_keys($this->adjustmentTypeManager->getDefinitions());
    $customer = $order->getCustomer();

    $currency_code = $order->getTotalPrice() ? $order->getTotalPrice()->getCurrencyCode() : $store->getDefaultCurrencyCode();
    $request_body = [
      'type' => $type,
      'companyCode' => $company_code,
      'date' => $date->format('c'),
      'code' => 'DC-' . $order->uuid(),
      'currencyCode' => $currency_code,
      'lines' => [],
    ];
    // Pass the tax exemption number|type if not empty.
    if (!$customer->isAnonymous()){
      if ($customer->hasField('avatax_tax_exemption_number') && !$customer->get('avatax_tax_exemption_number')->isEmpty()) {
        $request_body['ExemptionNo'] = $customer->get('avatax_tax_exemption_number')->value;
      }
      if ($customer->hasField('avatax_tax_exemption_type') && !$customer->get('avatax_tax_exemption_type')->isEmpty()) {
        $request_body['CustomerUsageType'] = $customer->get('avatax_tax_exemption_type')->value;
      }
      if ($customer->hasField('avatax_customer_code') && !$customer->get('avatax_customer_code')->isEmpty()) {
        $request_body['customerCode'] = $customer->get('avatax_customer_code')->value;
      }
      else {
        $customer_code_field = $this->config->get('customer_code_field');
        // For authenticated users, if the avatax_customer_code field is empty,
        // use the field configured in config (mail|uid).
        if ($order->hasField($customer_code_field) && !$order->get($customer_code_field)->isEmpty()) {
          $customer_code = $customer_code_field === 'mail' ? $order->getEmail() : $order->getCustomerId();
          $request_body['customerCode'] = $customer_code;
        }
      }
    }

    // If the customer code could not be determined (either because the customer
    // is anonymous or the mail is empty, fallback to the logic below).
    if (!isset($request_body['customerCode'])) {
      $request_body['customerCode'] = $order->getEmail() ?: 'anonymous-' . $order->id();
    }

    $has_shipments = $order->hasField('shipments') && !$order->get('shipments')->isEmpty();

    foreach ($order->getItems() as $order_item) {
      $profile = $this->resolveCustomerProfile($order_item);

      // If we could not resolve a profile for the order item, do not add it
      // to the API request. There may not be an address available yet, or the
      // item may not be shippable and not attached to a shipment.
      if (!$profile) {
        continue;
      }
      $purchased_entity = $order_item->getPurchasedEntity();

      /** @var \Drupal\address\Plugin\Field\FieldType\AddressItem $address */
      $address = $profile->get('address')->first();
      $line_item = [
        'number' => $order_item->uuid(),
        'quantity' => $order_item->getQuantity(),
        // When the transaction request is performed when an order is placed,
        // the order item already has a tax adjustment that we shouldn't send
        // to Avatax.
        'amount' => $order_item->getAdjustedTotalPrice(array_diff($adjustment_types, ['tax']))->getNumber(),
      ];

      // Send the "SKU" as the "itemCode".
      if ($purchased_entity instanceof ProductVariationInterface) {
        $line_item['itemCode'] = $purchased_entity->getSku();
      }
      if ($has_shipments) {
        $line_item['addresses'] = [
          'shipFrom' => self::formatAddress($store->getAddress()),
          'shipTo' => self::formatAddress($address),
        ];
      }
      else {
        $line_item['addresses']['singleLocation'] = self::formatAddress($address);
      }

      $line_item['taxCode'] = $this->chainTaxCodeResolver->resolve($order_item);
      $request_body['lines'][] = $line_item;
    }

    if ($has_shipments) {
      /** @var \Drupal\commerce_shipping\Entity\ShipmentInterface $shipment */
      foreach ($order->get('shipments')->referencedEntities() as $shipment) {
        $request_body['lines'][] = [
          'taxCode' => $this->config->get('shipping_tax_code'),
          'number' => $shipment->uuid(),
          'description' => $shipment->label(),
          'amount' => $shipment->getAmount()->getNumber(),
          'quantity' => 1,
          'addresses' => [
            'shipFrom' => self::formatAddress($store->getAddress()),
            'shipTo' => self::formatAddress($shipment->getShippingProfile()->get('address')->first()),
          ]
        ];
      }
    }

    // Send additional order adjustments as separate lines.
    foreach ($order->getAdjustments() as $adjustment) {
      // Skip shipping, fees and tax adjustments.
      if (in_array($adjustment->getType(), ['shipping', 'fee', 'tax'])) {
        continue;
      }
      $line_item = [
        // @todo: Figure out which taxCode to use here.
        'taxCode' => 'P0000000',
        'description' => $adjustment->getLabel(),
        'amount' => $adjustment->getAmount()->getNumber(),
        'quantity' => 1,
        'addresses' => [
          'shipFrom' => self::formatAddress($store->getAddress()),
        ],
      ];
      // Take the "shipTo" from the first line if present, otherwise just ignore
      // the adjustment, because sending lines without an "addresses" key
      // is only possible when a global "addresses" is specified at the
      // document level, which isn't the case here.
      if (isset($request_body['lines'][0]['addresses']['shipTo'])) {
        $line_item['addresses']['shipTo'] = $request_body['lines'][0]['addresses']['shipTo'];
        $request_body['lines'][] = $line_item;
      }
    }

    if ($request_body['type'] === 'SalesInvoice') {
      $request_body['commit'] = TRUE;
    }
    $this->moduleHandler->alter('commerce_avatax_order_request', $request_body, $order);

    return $request_body;
  }

  /**
   * Format an address for use in the order request.
   *
   * @param \Drupal\address\AddressInterface $address
   *   The address to format.
   *
   * @return array
   *   Return a formatted address for use in the order request.
   */
  protected static function formatAddress(AddressInterface $address) {
    return [
      'line1' => $address->getAddressLine1(),
      'line2' => $address->getAddressLine2(),
      'city' => $address->getLocality(),
      'region' => $address->getAdministrativeArea(),
      'country' => $address->getCountryCode(),
      'postalCode' => $address->getPostalCode(),
    ];
  }

  /**
   * Resolves the customer profile for the given order item.
   * Stolen from TaxTypeBase::resolveCustomerProfile().
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item.
   *
   * @return \Drupal\profile\Entity\ProfileInterface|null
   *   The customer profile, or NULL if not yet known.
   */
  protected function resolveCustomerProfile(OrderItemInterface $order_item) {
    $order = $order_item->getOrder();
    $customer_profile = $order->getBillingProfile();
    // A shipping profile is preferred, when available.
    $event = new CustomerProfileEvent($customer_profile, $order_item);
    $this->eventDispatcher->dispatch(TaxEvents::CUSTOMER_PROFILE, $event);
    $customer_profile = $event->getCustomerProfile();

    return $customer_profile;
  }

  /**
   * Performs a request.
   *
   * @param string $method
   *   The HTTP method to use.
   * @param string $path
   *   The remote path. The base URL will be automatically appended.
   * @param array $parameters
   *   An array of fields to include with the request. Optional.
   *
   * @return array
   *   The response array.
   */
  protected function doRequest($method, $path, array $parameters = []) {
    $response_body = [];
    try {
      $response = $this->client->request($method, $path, $parameters);
      $response_body = Json::decode($response->getBody()->getContents());
    }
    catch (ClientException $e) {
      $response_body = Json::decode($e->getResponse()->getBody()->getContents());
      $this->logger->error($e->getResponse()->getBody()->getContents());
    }
    catch (\Exception $e) {
      $this->logger->error($e->getMessage());
    }
    // Log the response/request if logging is enabled.
    if ($this->config->get('logging')) {
      $url = $this->client->getConfig('base_uri') . $path;
      $this->logger->info("URL: <pre>$method @url</pre>Headers: <pre>@headers</pre>Request: <pre>@request</pre>Response: <pre>@response</pre>", [
        '@url' => $url,
        '@headers' => Variable::export($this->client->getConfig('headers')),
        '@request' => Variable::export($parameters),
        '@response' => Variable::export($response_body),
      ]);
    }

    return $response_body;
  }

  /**
   * Sets the http client.
   *
   * @param \GuzzleHttp\Client $client
   *   The http client.
   *
   * @return $this
   */
  public function setClient(Client $client) {
    $this->client = $client;
    return $this;
  }

}
