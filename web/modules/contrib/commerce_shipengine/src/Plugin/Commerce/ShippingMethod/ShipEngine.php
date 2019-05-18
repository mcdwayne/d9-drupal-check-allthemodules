<?php

namespace Drupal\commerce_shipengine\Plugin\Commerce\ShippingMethod;

use Drupal\commerce_shipping\Entity\ShipmentInterface;
use Drupal\commerce_shipping\PackageTypeManagerInterface;
use Drupal\commerce_shipping\Plugin\Commerce\ShippingMethod\ShippingMethodBase;
use Drupal\commerce_shipengine\ShipEngineRequestInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


use Drupal\commerce_price\Price;
use Drupal\commerce_shipping\ShippingRate;
use Drupal\commerce_shipping\ShippingService;
use Drupal\Core\Url;

/**
 * @CommerceShippingMethod(
 *  id = "shipengine",
 *  label = @Translation("ShipEngine"),
 *  services = {
 *    "ups_standard_international" = @translation("UPS Standard®"),
 *    "ups_next_day_air_early_am" = @translation("UPS Next Day Air® Early"),
 *    "ups_worldwide_express" = @translation("UPS Worldwide Express®"),
 *    "ups_next_day_air" = @translation("UPS Next Day Air®"),
 *    "ups_ground_international" = @translation("UPS Ground® (International)"),
 *    "ups_worldwide_express_plus" = @translation("UPS Worldwide Express Plus®"),
 *    "ups_next_day_air_saver" = @translation("UPS Next Day Air Saver®"),
 *    "ups_worldwide_expedited" = @translation("UPS Worldwide Expedited®"),
 *    "ups_2nd_day_air_am" = @translation("UPS 2nd Day Air AM®"),
 *    "ups_2nd_day_air" = @translation("UPS Worldwide Express Plus®"),
 *    "ups_worldwide_saver" = @translation("UPS Worldwide Saver®"),
 *    "ups_2nd_day_air_international" = @translation("UPS 2nd Day Air® (International)"),
 *    "ups_3_day_select" = @translation("UPS 3 Day Select®"),
 *    "ups_ground" = @translation("UPS® Ground"),
 *    "ups_next_day_air_international" = @translation("UPS Next Day Air® (International)"),
 *    "usps_first_class_mail" = @translation("USPS First Class Mail"),
 *    "usps_media_mail" = @translation("USPS Media Mail"),
 *    "usps_parcel_select" = @translation("USPS Parcel Select Ground"),
 *    "usps_priority_mail" = @translation("USPS Priority Mail"),
 *    "usps_priority_mail_express" = @translation("USPS Priority Mail Express"),
 *    "usps_first_class_mail_international" = @translation("USPS First Class Mail Intl"),
 *    "usps_priority_mail_international" = @translation("USPS Priority Mail Intl"),
 *    "usps_priority_mail_express_international" = @translation("USPS Priority Mail Express Intl"),
 *  }
 * )
 */
class ShipEngine extends ShippingMethodBase {
  /**
   * @var \Drupal\commerce_shipengine\ShipEngineRateRequest
   */
  protected $shipengine_rate_service;

  /**
   * Constructs a new ShipEngine object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\commerce_shipping\PackageTypeManagerInterface $packageTypeManager
   *   The package type manager.
   * @param \Drupal\commerce_ups\UPSRequestInterface $ups_rate_request
   *   The rate request service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PackageTypeManagerInterface $packageTypeManager, ShipEngineRequestInterface $shipengine_rate_request) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $packageTypeManager);
    $this->shipengine_rate_service = $shipengine_rate_request;
    $this->shipengine_rate_service->setConfig($configuration);
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
      $container->get('commerce_shipengine.rate_request')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'api_information' => [
        'api_key' => '',
        'stamps_id' => '',
        'ups_id' => '',
      ],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['api_information'] = [
      '#type' => 'details',
      '#title' => $this->t('API information'),
      '#description' => $this->isConfigured() ? $this->t('Update your ShipEngine API information.') : $this->t('Fill in your ShipEngine API information.'),
      '#weight' => $this->isConfigured() ? 10 : -10,
      '#open' => !$this->isConfigured(),
    ];

    $form['api_information']['api_key'] = [
      '#type' => 'textfield',
      '#title' => t('API Key'),
      '#default_value' => $this->configuration['api_information']['api_key'],
      '#required' => TRUE,
    ];

    $form['api_information']['stamps_id'] = [
      '#type' => 'textfield',
      '#title' => t('Stamps.com carrier ID'),
      '#default_value' => $this->configuration['api_information']['stamps_id'],
      '#required' => TRUE,
    ];

    $form['api_information']['ups_id'] = [
      '#type' => 'textfield',
      '#title' => t('UPS carrier ID'),
      '#default_value' => $this->configuration['api_information']['ups_id'],
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);

      $this->configuration['services'] = $values['services'];
      $this->configuration['api_information']['api_key'] = $values['api_information']['api_key'];
      $this->configuration['api_information']['stamps_id'] = $values['api_information']['stamps_id'];
      $this->configuration['api_information']['ups_id'] = $values['api_information']['ups_id'];
    }

    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * Calculates rates for the given shipment.
   *
   * @param \Drupal\commerce_shipping\Entity\ShipmentInterface $shipment
   *   The shipment.
   *
   * @return \Drupal\commerce_shipping\ShippingRate[]
   *   The rates.
   */
  public function calculateRates(ShipmentInterface $shipment) {
    $rates = [];

    // Only attempt to collect rates if an address exits on the shipment.
    if (!$shipment->getShippingProfile()->get('address')->isEmpty()) {
      $this->shipengine_rate_service->setShipment($shipment);
      $this->shipengine_rate_service->setConfig($this->configuration);
      $rates = $this->shipengine_rate_service->getRates();
    }

    return $rates;
  }

  /**
   * Determine if we have the minimum information to connect to ShipEngine.
   *
   * @return bool
   *   TRUE if there is enough information to connect, FALSE otherwise.
   */
  protected function isConfigured() {
    $api_information = $this->configuration['api_information'];

    return (!empty($api_information['api_key']));
  }

}
