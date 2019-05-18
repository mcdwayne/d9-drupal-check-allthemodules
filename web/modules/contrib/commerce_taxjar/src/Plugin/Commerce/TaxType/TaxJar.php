<?php

namespace Drupal\commerce_taxjar\Plugin\Commerce\TaxType;

use Drupal\commerce_taxjar\ClientFactory;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Adjustment;
use Drupal\commerce_price\Price;
use Drupal\commerce_tax\Plugin\Commerce\TaxType\RemoteTaxTypeBase;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides the TaxJar remote tax type.
 *
 * @CommerceTaxType(
 *   id = "taxjar",
 *   label = "TaxJar",
 * )
 */
class TaxJar extends RemoteTaxTypeBase {

  /**
   * The client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs a new TaxJar object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\commerce_taxjar\ClientFactory $client_factory
   *   The client.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EventDispatcherInterface $event_dispatcher, ClientFactory $client_factory, ModuleHandlerInterface $module_handler, LoggerChannelFactoryInterface $logger_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $event_dispatcher);
    $this->client = $client_factory->createInstance($this->configuration);
    $this->moduleHandler = $module_handler;
    $this->logger = $logger_factory->get('commerce_taxjar');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('event_dispatcher'),
      $container->get('commerce_taxjar.client_factory'),
      $container->get('module_handler'),
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'display_inclusive' => FALSE,
      'api_key' => '',
      'sandbox_key' => '',
      'api_mode' => 'production',
      'enable_reporting' => TRUE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['api_mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('API mode:'),
      '#default_value' => $this->configuration['api_mode'],
      '#options' => [
        'production' => $this->t('Production'),
        'sandbox' => $this->t('Sandbox'),
      ],
      '#required' => TRUE,
      '#description' => $this->t('The mode to use when calculating taxes. Note: Sandbox mode is only available with TaxJar Plus.'),
    ];

    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Token'),
      '#default_value' => $this->configuration['api_key'],
      '#required' => TRUE,
      '#description' => $this->t('Enter your TaxJar API token. If you do not have a token, <a href="@login" target="_blank">login</a> and go to Account > API Access to generate a new one.', ['@login' => 'https://app.taxjar.com']),
    ];

    $form['sandbox_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sandbox API Token'),
      '#default_value' => $this->configuration['sandbox_key'],
      '#description' => $this->t('Enter your sandbox API token for testing.'),
      '#states' => [
        'visible' => [
          ':input[name="configuration[taxjar][api_mode]"]' => ['value' => 'sandbox'],
        ],
        'required' => [
          ':input[name="configuration[taxjar][api_mode]"]' => ['value' => 'sandbox'],
        ],
      ],
    ];

    $form['enable_reporting'] = [
      '#type' => 'checkbox',
      '#title' => t('Use TaxJar for sales tax reporting'),
      '#description' => t('Record order transactions to TaxJar for automated reporting / filing.'),
      '#default_value' => $this->configuration['enable_reporting'],
    ];

    $categories_found = (bool) \Drupal::entityQuery('taxonomy_term')
      ->condition('vid', 'taxjar_categories')
      ->count()
      ->execute();

    $form['sync_categories'] = [
      '#type' => 'checkbox',
      '#title' => t('Sync Product Tax Categories'),
      '#description' => t('TaxJar supplies certain product categories which are used to tag products that are either exempt from sales tax in some jurisdictions or are taxed at reduced rates. These categories are fetched at the time of module installation, and will typically not require updating. If a new category has been added by TaxJar which you would like to use, check this box and submit the form to fetch any updated categories from the TaxJar service.'),
      '#default_value' => !$categories_found,
      '#access' => $categories_found,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $values = $form_state->getValue($form['#parents']);
    $this->configuration['api_mode'] = $values['api_mode'];
    $key_added = empty($this->configuration['api_key']) && !empty($values['api_key']);
    $this->configuration['api_key'] = $values['api_key'];
    $this->configuration['sandbox_key'] = $values['sandbox_key'];
    $this->configuration['enable_reporting'] = (bool) $values['enable_reporting'];

    if ((bool) $values['sync_categories']) {
      if ($key_added) {
        // Get a new client configured with the newly added api token before attempting to sync.
        $this->client = \Drupal::service('commerce_taxjar.client_factory')->createInstance($this->configuration);
      }
      $this->syncCategories();
    }
  }

  /**
   * Sync TaxJar categories.
   */
  public function syncCategories() {
    try {
      $response = $this->client->get('categories');

      $categories = Json::decode($response->getBody()->getContents());

      $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');

      $terms = $term_storage->loadByProperties(['vid' => 'taxjar_categories']);

      // Build array of existing categories keyed by TaxJar category code.
      $existing_terms = [];
      foreach ($terms as $term) {
        $existing_terms[$term->taxjar_category_code->value] = $term;
      }

      // Loop through categories, updating or adding as necessary.
      foreach ($categories['categories'] as $category) {
        if (empty($existing_terms[$category['product_tax_code']])) {
          $term = $term_storage->create([
            'vid' => 'taxjar_categories',
          ]);
        }
        else {
          $term = $existing_terms[$category['product_tax_code']];
        }
        $term->set('name', $category['name']);
        $term->set('taxjar_category_code', $category['product_tax_code']);
        $term->set('description', $category['description']);
        $term->save();
      }
      drupal_set_message(t('TaxJar product categories updated.'));
    }
    catch (ClientException $e) {
      $this->logger->error($e->getResponse()->getBody()->getContents());
    }
    catch (\Exception $e) {
      $this->logger->error($e->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function apply(OrderInterface $order) {
    $request_body = $this->buildRequest($order);

    if (empty($request_body)) {
      return;
    }

    try {
      // If identical request has already been made, reuse it.
      $order_data = $order->getData($this->pluginId);
      if (!empty($order_data['request']) && !empty($order_data['response']) && $order_data['request'] == $request_body) {
        $response_body = $order_data['response'];
      }
      else {
        $response = $this->client->post('taxes', [
          'json' => $request_body,
        ]);

        $response_body = Json::decode($response->getBody()->getContents());
      }

      $items_tax = [];
      if (!empty($response_body['tax']['breakdown'])) {
        foreach ($response_body['tax']['breakdown']['line_items'] as $item) {
          $items_tax[$item['id']] = $item['tax_collectable'];
        }
      }

      $currency_code = $order->getTotalPrice() ? $order->getTotalPrice()->getCurrencyCode() : $order->getStore()->getDefaultCurrencyCode();

      foreach ($order->getItems() as $item) {
        if (isset($items_tax[$item->id()])) {
          $item->addAdjustment(new Adjustment([
            'type' => 'tax',
            'label' => 'Sales tax',
            'amount' => new Price((string) $items_tax[$item->id()], $currency_code),
            'source_id' => $this->pluginId . '|' . $this->entityId,
          ]));
        }
      }

      // Store the TaxJar data in the order.
      $order->setData($this->pluginId, [
        'plugin_id' => $this->entityId,
        'request' => $request_body,
        'response' => $response_body,
      ]);
    }
    catch (ClientException $e) {
      $this->logger->error($e->getResponse()->getBody()->getContents());
    }
    catch (\Exception $e) {
      $this->logger->error($e->getMessage());
    }
  }

  /**
   * Make create transaction request to API.
   */
  public function createTransaction(OrderInterface $order) {
    $request = $this->buildRequest($order, 'transaction');

    try {
      $this->client->post('transactions/orders', [
        'json' => $request,
      ]);

      $data = $order->getData($this->pluginId);
      $data['transactionRequest'] = $request;
      $order->setData($this->pluginId, $data);
    }
    catch (ClientException $e) {
      $this->logger->error($e->getResponse()->getBody()->getContents());
    }
    catch (\Exception $e) {
      $this->logger->error($e->getMessage());
    }
  }

  /**
   * Make transaction update request to API.
   */
  public function updateTransaction(OrderInterface $order) {
    $request = $this->buildRequest($order, 'transaction');
    $order_data = $order->getData($this->pluginId);
    $old_request = empty($order_data['transactionRequest']) ? [] : $order_data['transactionRequest'];

    // Only make request if the order has changed.
    if ($request != $old_request) {
      try {
        $this->client->put('transactions/orders/' . $order->getOrderNumber(), [
          'json' => $request,
        ]);

        $order_data['transactionRequest'] = $request;
        $order->setData($this->pluginId, $order_data);
      }
      catch (ClientException $e) {
        $this->logger->error($e->getResponse()->getBody()->getContents());
      }
      catch (\Exception $e) {
        $this->logger->error($e->getMessage());
      }
    }
  }

  /**
   * Make refund transaction request to API.
   */
  public function refundTransaction(OrderInterface $order, string $amount) {
    $request = $this->buildRequest($order, 'transaction');

    $request['transaction_reference_id'] = $request['transaction_id'];
    $request['transaction_id'] .= '-refund';

    $request['amount'] = $amount - $request['sales_tax'];

    $refund_exists = TRUE;

    // Check for existing refund transaction.
    try {
      $this->client->get('transactions/refunds/' . $request['transaction_id']);
    }
    catch (ClientException $e) {
      if ($e->getResponse()->getStatusCode() == 404) {
        $refund_exists = FALSE;
      }
    }

    if ($refund_exists) {
      // Update existing refund transaction.
      try {
        $this->client->put('transactions/refunds/' . $request['transaction_id'], [
          'json' => $request,
        ]);
      }
      catch (ClientException $e) {
        $this->logger->error($e->getResponse()->getBody()->getContents());
      }
      catch (\Exception $e) {
        $this->logger->error($e->getMessage());
      }
    }
    else {
      // Create new refund transaction.
      try {
        $this->client->post('transactions/refunds', [
          'json' => $request,
        ]);
      }
      catch (ClientException $e) {
        $this->logger->error($e->getResponse()->getBody()->getContents());
      }
      catch (\Exception $e) {
        $this->logger->error($e->getMessage());
      }
    }
  }

  /**
   * Make transaction delete request to API.
   */
  public function deleteTransaction(OrderInterface $order) {
    $refund_exists = TRUE;

    // Check for corresponding refund transaction.
    try {
      $this->client->get('transactions/refunds/' . $order->getOrderNumber() . '-refund');
    }
    catch (ClientException $e) {
      if ($e->getResponse()->getStatusCode() == 404) {
        $refund_exists = FALSE;
      }
    }

    // Delete refund if it exists.
    if ($refund_exists) {
      try {
        $this->client->delete('transactions/refunds/' . $order->getOrderNumber() . '-refund');
      }
      catch (ClientException $e) {
        $this->logger->error($e->getResponse()->getBody()->getContents());
      }
      catch (\Exception $e) {
        $this->logger->error($e->getMessage());
      }
    }

    // Delete order.
    try {
      $this->client->delete('transactions/orders/' . $order->getOrderNumber());
    }
    catch (ClientException $e) {
      $this->logger->error($e->getResponse()->getBody()->getContents());
    }
    catch (\Exception $e) {
      $this->logger->error($e->getMessage());
    }
  }

  /**
   * Build request.
   */
  public function buildRequest(OrderInterface $order, $mode = 'quote') {
    $request_body = [
      'plugin' => 'drupal-commerce',
      'shipping' => 0,
      'line_items' => [],
    ];

    $store = $order->getStore();

    if ($store->taxjar_address_mode->value === 'store') {
      $address = $store->getAddress();
      $request_body['from_country'] = $address->getCountryCode();
      $request_body['from_zip'] = $address->getPostalCode();
      $request_body['from_state'] = $address->getAdministrativeArea();
      $request_body['from_city'] = $address->getLocality();
      $request_body['from_street'] = $address->getAddressLine1();
      // Concatenate street line 2 if supplied.
      if (!empty($address->getAddressLine2())) {
        $request_body['from_street'] .= ' ' . $address->getAddressLine2();
      }
    }

    $order_line_items = $order->getItems();

    // Add to address.
    foreach ($order_line_items as $item) {
      if (!empty($item->getOrder())) {
        $profile = $this->resolveCustomerProfile($item);
        if (!empty($profile)) {
          $address = $profile->get('address')->first();
          $request_body['to_country'] = $address->getCountryCode();
          $request_body['to_zip'] = $address->getPostalCode();
          $request_body['to_state'] = $address->getAdministrativeArea();
          $request_body['to_city'] = $address->getLocality();
          $request_body['to_street'] = $address->getAddressLine1();
          // Concatenate street line 2 if supplied.
          if (!empty($address->getAddressLine2())) {
            $request_body['to_street'] .= ' ' . $address->getAddressLine2();
          }
          break;
        }
      }
    }

    // Stop here if a 'to' address could not be obtained.
    if (empty($request_body['to_country'])) {
      return;
    }

    // Add line items.
    foreach ($order_line_items as $item) {
      $line_item = [
        'id' => $item->id(),
        'quantity' => $item->getQuantity(),
        'unit_price' => $item->getUnitPrice()->getNumber(),
      ];

      if ($term = $item->getPurchasedEntity()->taxjar_category_code->entity) {
        $line_item['product_tax_code'] = $term->taxjar_category_code->value;
      }

      $discount = 0;

      foreach ($item->getAdjustments() as $adjustment) {
        if ($adjustment->getType() === 'promotion') {
          $discount = $discount - $adjustment->getAmount()->getNumber();
        }
      }

      if ($discount !== 0) {
        $line_item['discount'] = $discount;
      }

      $request_body['line_items'][] = $line_item;
    }

    $adjustments = $order->getAdjustments();

    foreach ($adjustments as $adjustment) {
      if ($adjustment->getType() === 'shipping') {
        $request_body['shipping'] += $adjustment->getAmount()->getNumber();
      }
    }

    // Let other modules alter the request.
    $this->moduleHandler->alter('commerce_taxjar_tax_request', $request_body, $order);

    if ($mode === 'transaction') {
      unset($request_body['plugin']);
      $request_body['transaction_id'] = $order->getOrderNumber();
      $request_body['transaction_date'] = DrupalDateTime::createFromTimestamp($order->getPlacedTime())->format('Y-m-d');
      $request_body['shipping'] = 0;
      $request_body['sales_tax'] = 0;
      $line_items = [];
      foreach ($request_body['line_items'] as $item) {
        $line_items[$item['id']] = $item;
      }

      foreach ($order_line_items as $line_item) {
        $id = $line_item->id();
        $adjustment_amount = 0;
        $adjustments = $line_item->getAdjustments();
        foreach ($adjustments as $adjustment) {
          if (strpos($adjustment->getSourceId(), 'taxjar|') !== FALSE) {
            $adjustment_amount += $adjustment->getAmount()->getNumber();
          }
        }
        $line_items[$id]['sales_tax'] = $adjustment_amount;
        $request_body['sales_tax'] += $adjustment_amount;
        $product = $line_item->getPurchasedEntity();
        $line_items[$id]['product_identifier'] = $product->getSku();
        $line_items[$id]['description'] = $product->getTitle();
      }

      $request_body['line_items'] = array_values($line_items);

      $adjustments = $order->getAdjustments();

      foreach ($adjustments as $adjustment) {
        if ($adjustment->getType() === 'shipping') {
          $request_body['shipping'] += $adjustment->getAmount()->getNumber();
        }
      }

      $request_body['amount'] = $order->getTotalPrice()->getNumber() - $request_body['sales_tax'];

      // Let other modules alter the request.
      $this->moduleHandler->alter('commerce_taxjar_transaction_request', $request_body, $order);
    }

    return $request_body;
  }

  /**
   * Get the API client, configured for this instance.
   *
   * @return \GuzzleHttp\Client
   *   The httpClient instance.
   */
  public function getClient() {
    return $this->client;
  }

}
