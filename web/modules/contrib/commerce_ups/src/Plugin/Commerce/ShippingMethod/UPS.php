<?php

namespace Drupal\commerce_ups\Plugin\Commerce\ShippingMethod;

use Drupal\commerce_shipping\Entity\ShipmentInterface;
use Drupal\commerce_shipping\PackageTypeManagerInterface;
use Drupal\commerce_shipping\Plugin\Commerce\ShippingMethod\ShippingMethodBase;
use Drupal\commerce_shipping\Plugin\Commerce\ShippingMethod\SupportsTrackingInterface;
use Drupal\commerce_ups\UPSRateRequestInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the UPS shipping method.
 *
 * @CommerceShippingMethod(
 *  id = "ups",
 *  label = @Translation("UPS"),
 *  services = {
 *    "_01" = @translation("UPS Next Day Air"),
 *    "_02" = @translation("UPS Second Day Air"),
 *    "_03" = @translation("UPS Ground"),
 *    "_07" = @translation("UPS Worldwide Express"),
 *    "_08" = @translation("UPS Worldwide Expedited"),
 *    "_11" = @translation("UPS Standard"),
 *    "_12" = @translation("UPS Three-Day Select"),
 *    "_13" = @translation("Next Day Air Saver"),
 *    "_14" = @translation("UPS Next Day Air Early AM"),
 *    "_54" = @translation("UPS Worldwide Express Plus"),
 *    "_59" = @translation("UPS Second Day Air AM"),
 *    "_65" = @translation("UPS Saver"),
 *    "_70" = @translation("UPS Access Point Economy"),
 *  }
 * )
 */
class UPS extends ShippingMethodBase implements SupportsTrackingInterface {

  /**
   * The service for fetching shipping rates from UPS.
   *
   * @var \Drupal\commerce_ups\UPSRateRequestInterface
   */
  protected $upsRateService;

  /**
   * Constructs a new UPS object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\commerce_shipping\PackageTypeManagerInterface $package_type_manager
   *   The package type manager.
   * @param \Drupal\commerce_ups\UPSRateRequestInterface $ups_rate_request
   *   The rate request service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PackageTypeManagerInterface $package_type_manager, UPSRateRequestInterface $ups_rate_request) {
    // Rewrite the service keys to be integers.
    $plugin_definition = $this->preparePluginDefinition($plugin_definition);

    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $package_type_manager
    );

    $this->upsRateService = $ups_rate_request;
    $this->upsRateService->setConfig($configuration);
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
      $container->get('commerce_ups.ups_rate_request')
    );
  }

  /**
   * Prepares the service array keys to support integer values.
   *
   * See https://www.drupal.org/node/2904467 for more information.
   *
   * TODO: Remove once core issue has been addressed.
   *
   * @param array $plugin_definition
   *   The plugin definition provided to the class.
   *
   * @return array
   *   The prepared plugin definition.
   */
  private function preparePluginDefinition(array $plugin_definition) {
    // Cache and unset the parsed plugin definitions for services.
    $services = $plugin_definition['services'];
    unset($plugin_definition['services']);

    // Loop over each service definition and redefine them with
    // integer keys that match the UPS API.
    foreach ($services as $key => $service) {
      // Remove the "_" from the service key.
      $key_trimmed = str_replace('_', '', $key);
      $plugin_definition['services'][$key_trimmed] = $service;
    }

    return $plugin_definition;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'api_information' => [
        'access_key' => '',
        'user_id' => '',
        'password' => '',
        'mode' => 'test',
      ],
      'rate_options' => [
        'rate_type' => 0,
      ] ,
      'options' => [
        'tracking_url' => 'https://wwwapps.ups.com/tracking/tracking.cgi?tracknum=[tracking_code]',
        'log' => [],
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

    $description = $this->t('Update your UPS API information');
    if (!$this->isConfigured()) {
      $description = $this->t('Fill in your UPS API information.');
    }
    $form['api_information'] = [
      '#type' => 'details',
      '#title' => $this->t('API information'),
      '#description' => $description,
      '#weight' => $this->isConfigured() ? 10 : -10,
      '#open' => !$this->isConfigured(),
    ];

    $form['api_information']['access_key'] = [
      '#type' => 'textfield',
      '#title' => t('Access Key'),
      '#default_value' => $this->configuration['api_information']['access_key'],
      '#required' => TRUE,
    ];
    $form['api_information']['user_id'] = [
      '#type' => 'textfield',
      '#title' => t('User ID'),
      '#default_value' => $this->configuration['api_information']['user_id'],
      '#required' => TRUE,
    ];

    $form['api_information']['password'] = [
      '#type' => 'textfield',
      '#title' => t('Password'),
      '#default_value' => $this->configuration['api_information']['password'],
      '#required' => TRUE,
    ];

    $form['api_information']['mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Mode'),
      '#description' => $this->t('Choose whether to use the test or live mode.'),
      '#options' => [
        'test' => $this->t('Test'),
        'live' => $this->t('Live'),
      ],
      '#default_value' => $this->configuration['api_information']['mode'],
    ];

    $form['rate_options'] = [
      '#type' => 'details',
      '#title' => $this->t('Rate options'),
      '#description' => $this->t('Options to pass during rate requests.'),
    ];

    $form['rate_options']['rate_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Rate Type'),
      '#description' => $this->t('Choose between negotiated and standard rates.'),
      '#options' => [
        0 => $this->t('Standard Rates'),
        1 => $this->t('Negotiated Rates'),
      ],
      '#default_value' => $this->configuration['rate_options']['rate_type'],
    ];

    $form['options'] = [
      '#type' => 'details',
      '#title' => $this->t('UPS Options'),
      '#description' => $this->t('Additional options for UPS'),
    ];
    $form['options']['tracking_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Tracking URL base'),
      '#description' => $this->t(
        'The base URL for assembling a tracking URL. If the [tracking_code]
         token is omitted, the code will be appended to the end of the URL
          (e.g. "https://wwwapps.ups.com/tracking/tracking.cgi?tracknum=123456789")'
      ),
      '#default_value' => $this->configuration['options']['tracking_url'],
    ];
    $form['options']['log'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Log the following messages for debugging'),
      '#options' => [
        'request' => $this->t('API request messages'),
        'response' => $this->t('API response messages'),
      ],
      '#default_value' => $this->configuration['options']['log'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);

      $this->configuration['api_information']['access_key'] = $values['api_information']['access_key'];
      $this->configuration['api_information']['user_id'] = $values['api_information']['user_id'];
      $this->configuration['api_information']['password'] = $values['api_information']['password'];
      $this->configuration['api_information']['mode'] = $values['api_information']['mode'];
      $this->configuration['rate_options']['rate_type'] = $values['rate_options']['rate_type'];
      $this->configuration['options']['log'] = $values['options']['log'];

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
    // Only attempt to collect rates if an address exists on the shipment.
    if ($shipment->getShippingProfile()->get('address')->isEmpty()) {
      return [];
    }

    return $this->upsRateService->getRates($shipment, $this);
  }

  /**
   * Returns a tracking URL for UPS shipments.
   *
   * @param \Drupal\commerce_shipping\Entity\ShipmentInterface $shipment
   *   The commerce shipment.
   *
   * @return mixed
   *   The URL object or FALSE.
   */
  public function getTrackingUrl(ShipmentInterface $shipment) {
    $code = $shipment->getTrackingCode();

    if (!empty($code)) {
      // If the tracking code token exists, replace it with the code.
      if (strstr($this->configuration['options']['tracking_url'], '[tracking_code]')) {
        $url = str_replace('[tracking_code]', $code, $this->configuration['options']['tracking_url']);
        return Url::fromUri($url);
      }

      // Otherwise, append the tracking code to the end of the URL.
      $url = $this->configuration['options']['tracking_url'] . $code;
      return Url::fromUri($url);
    }

    return FALSE;
  }

  /**
   * Determine if we have the minimum information to connect to UPS.
   *
   * @return bool
   *   TRUE if there is enough information to connect, FALSE otherwise.
   */
  protected function isConfigured() {
    $api_config = &$this->configuration['api_information'];

    if (empty($api_config['access_key']) || empty($api_config['user_id']) || empty($api_config['password'])) {
      return FALSE;
    }

    return TRUE;
  }

}
