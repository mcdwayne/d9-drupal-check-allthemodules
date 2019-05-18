<?php

namespace Drupal\commerce_usps\Plugin\Commerce\ShippingMethod;

use Drupal\commerce_shipping\Entity\ShipmentInterface;
use Drupal\commerce_shipping\PackageTypeManagerInterface;
use Drupal\commerce_shipping\Plugin\Commerce\ShippingMethod\ShippingMethodBase;
use Drupal\commerce_shipping\Plugin\Commerce\ShippingMethod\SupportsTrackingInterface;
use Drupal\commerce_usps\USPSRateRequestInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class USPSBase extends ShippingMethodBase implements SupportsTrackingInterface {

  /**
   * The USPSRateRequest class.
   *
   * @var \Drupal\commerce_usps\USPSRateRequestInterface
   */
  protected $uspsRateService;

  /**
   * Constructs a new ShippingMethodBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\commerce_shipping\PackageTypeManagerInterface $package_type_manager
   *   The package type manager.
   * @param \Drupal\commerce_usps\USPSRateRequestInterface $usps_rate_request
   *   The rate request service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PackageTypeManagerInterface $package_type_manager, USPSRateRequestInterface $usps_rate_request) {
    $plugin_definition = $this->preparePluginDefinition($plugin_definition);
    parent::__construct($configuration, $plugin_id, $plugin_definition, $package_type_manager);

    $this->uspsRateService = $usps_rate_request;
    $this->uspsRateService->setConfig($configuration);
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
      $container->get('commerce_usps.usps_rate_request')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'api_information' => [
        'user_id' => '',
        'password' => '',
        'mode' => 'test',
      ],
      'options' => [
        'tracking_url' => 'https://tools.usps.com/go/TrackConfirmAction?tLabels=[tracking_code]',
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

    $description = $this->t('Update your USPS API information.');
    if (!$this->isConfigured()) {
      $description = $this->t('Fill in your USPS API information.');
    }
    $form['api_information'] = [
      '#type' => 'details',
      '#title' => $this->t('API information'),
      '#description' => $description,
      '#weight' => $this->isConfigured() ? 10 : -10,
      '#open' => !$this->isConfigured(),
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
      '#description' => $this->t('Choose whether to use test or live mode.'),
      '#options' => [
        'test' => $this->t('Test'),
        'live' => $this->t('Live'),
      ],
      '#default_value' => $this->configuration['api_information']['mode'],
    ];

    $form['options'] = [
      '#type' => 'details',
      '#title' => $this->t('USPS Options'),
      '#description' => $this->t('Additional options for USPS'),
    ];

    $form['options']['tracking_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Tracking URL base'),
      '#description' => $this->t('The base URL for assembling a tracking URL. If the [tracking_code] token is omitted, the code will be appended to the end of the URL (e.g. "https://tools.usps.com/go/TrackConfirmAction?tLabels=123456789")'),
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

      $this->configuration['api_information']['user_id'] = $values['api_information']['user_id'];
      $this->configuration['api_information']['password'] = $values['api_information']['password'];
      $this->configuration['api_information']['mode'] = $values['api_information']['mode'];
      $this->configuration['options']['log'] = $values['options']['log'];
    }
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * Prepares the service array keys to support integer values.
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
    // TODO: Remove once core issue has been addressed.
    // See: https://www.drupal.org/node/2904467 for more information.
    foreach ($services as $key => $service) {
      // Remove the "_" from the service key.
      $key_trimmed = str_replace('_', '', $key);
      $plugin_definition['services'][$key_trimmed] = $service;
    }

    // Sort the options alphabetically.
    uasort($plugin_definition['services'], function (TranslatableMarkup $a, TranslatableMarkup $b) {
      return $a->getUntranslatedString() < $b->getUntranslatedString() ? -1 : 1;
    });

    return $plugin_definition;
  }

  /**
   * Ensure a package type exists on the shipment.
   *
   * @param \Drupal\commerce_shipping\Entity\ShipmentInterface $shipment
   *   The commerce shipment entity.
   */
  protected function setPackageType(ShipmentInterface $shipment) {
    if (!$shipment->getPackageType()) {
      $shipment->setPackageType($this->getDefaultPackageType());
    }
  }

  /**
   * Returns a tracking URL for USPS shipments.
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
      }
      else {
        // Otherwise, append the tracking code to the end of the URL.
        $url = $this->configuration['options']['tracking_url'] . $code;
      }

      return Url::fromUri($url);
    }
    return FALSE;
  }

  /**
   * Determine if we have the minimum information to connect to USPS.
   *
   * @return bool
   *   TRUE if there is enough information to connect, FALSE otherwise.
   */
  protected function isConfigured() {
    $api_config = $this->configuration['api_information'];

    if (empty($api_config['user_id']) || empty($api_config['password'])) {
      return FALSE;
    }

    return TRUE;
  }

}
