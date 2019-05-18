<?php

namespace Drupal\commerce_nzpost\Plugin\Commerce\ShippingMethod;

use Drupal\commerce_nzpost\NZPostRequestInterface;
use Drupal\commerce_nzpost\NZPostPluginManager;
use Drupal\commerce_price\Price;
use Drupal\commerce_shipping\Entity\ShipmentInterface;
use Drupal\commerce_shipping\PackageTypeManagerInterface;
use Drupal\commerce_shipping\Plugin\Commerce\ShippingMethod\ShippingMethodBase;
use Drupal\commerce_shipping\ShippingRate;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\DefaultLazyPluginCollection;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\commerce_nzpost\Packer\CommerceNzPostPacker as Packer;
use Drupal\commerce_nzpost\RateLookupService;

/**
 * Provides the NzPost shipping method.
 *
 * @CommerceShippingMethod(
 *   id = "nzpost",
 *   label = @Translation("NZ Post"),
 *   services = {
 *     "TIALP" = @Translation("3 to 10 working day, Untracked (International Air Large Package)"),
 *     "TIASPC" = @Translation("3 to 10 working day, Untracked (International Air Small Package)"),
 *     "TIEC" = @Translation("2 to 6 working day, Tracked (International Economy Courier)"),
 *     "TIEX" = @Translation("1 to 5 working day, Tracked (International Express Courier)"),
 *   }
 * )
 */
class NzPost extends ShippingMethodBase {

  /**
   * Constant for Domestic Shipping.
   * NOT IMPLEMENTED AT PRESENT.
   */
  const SHIPPING_TYPE_DOMESTIC = 'domestic';

  /**
   * Constant for International Shipping.
   * NOT IN USE AT PRESENT, EVERYTHING IS INTERNATIONAL.
   */
  const SHIPPING_TYPE_INTERNATIONAL = 'intl';

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The Service Plugins.
   *
   * @var \Drupal\Core\Plugin\DefaultLazyPluginCollection
   */
  protected $plugins;

  /**
   * Commerce NzPost Logger Channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $watchdog;

  /**
   * The NZPost Connection.
   *
   * @var \Drupal\commerce_nzpost\NZPostServiceManager
   */
  protected $nzPostServiceManager;

  /**
   * @var \Drupal\commerce_shipping\ShippingService;
   */
  protected $services;

  /**
   * @var \Drupal\commerce_nzpost\RateLookupService
   */
  protected $rateLookupService;

  /**
   * @var \Drupal\commerce_nzpost\Packer
   */
  protected $packer;
  /**
   * Constructs a new NzPost object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PackageTypeManagerInterface $package_type_manager, EventDispatcherInterface $event_dispatcher, RateLookupService $rate_lookup_service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $package_type_manager);

    $this->rateLookupService = $rate_lookup_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.commerce_package_type'),
      $container->get('event_dispatcher'),
      $container->get('commerce_nzpost.ratelookup')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {

    return [
        'api_information' => [
          'api_key' => '',
        ],
      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // Select all services by default.
    if (empty($this->configuration['services'])) {
      $service_ids = array_keys($this->services);
      $this->configuration['services'] = array_combine($service_ids, $service_ids);
    }

    $form['api_information'] = [
      '#type' => 'details',
      '#title' => $this->t('API information'),
      '#description' => $this->isConfigured() ? $this->t('Update your NZ Post API information.') : $this->t('Fill in your NZPost API information.'),
      '#weight' => $this->isConfigured() ? 10 : -10,
      '#open' => !$this->isConfigured(),
    ];

    $form['api_information']['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API key'),
      '#description' => $this->t('Enter your NZ Post API key.'),
      '#default_value' => $this->configuration['api_information']['api_key'],
    ];


    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {

    parent::validateConfigurationForm($form, $form_state);

  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    if (!$form_state->getErrors()) {

      $values = $form_state->getValue($form['#parents']);

      $this->configuration['api_information']['api_key'] = $values['api_information']['api_key'];
    }
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateRates(ShipmentInterface $shipment) {

       $availableRates = [];

    if ($shipment->getShippingProfile()->address->isEmpty()) {
      return [];
    }

    if (empty($shipment->getPackageType())) {
      $shipment->setPackageType($this->getDefaultPackageType());
    }

    $rates = $this->rateLookupService->getRates($shipment, $this->configuration);

    foreach ($rates as $key => $rate) {
      $price  = new Price($rate['price_including_gst'], 'NZD');
      if (array_key_exists($key, $this->getServices())) {
        $availableRates[$rate['price_including_gst']] = new ShippingRate($key,  $this->services[$key], $price);
      }
    }
    // Sort by price ASC.
    ksort($availableRates);
    return $availableRates;
  }

  /**
   * Determine if we have the minimum information to connect to NZPost.
   *
   * @return bool
   *   TRUE if there is enough information to connect, FALSE otherwise.
   */
  protected function isConfigured() {
    $api_information = $this->configuration['api_information'];

    if (!empty($api_information['api_key'])) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

}
