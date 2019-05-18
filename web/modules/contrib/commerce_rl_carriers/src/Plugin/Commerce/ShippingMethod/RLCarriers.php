<?php

namespace Drupal\commerce_rl_carriers\Plugin\Commerce\ShippingMethod;

use Drupal\commerce_shipping\Entity\ShipmentInterface;
use Drupal\commerce_shipping\PackageTypeManagerInterface;
use Drupal\commerce_shipping\Plugin\Commerce\ShippingMethod\ShippingMethodBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_shipping\ShippingService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\commerce_rl_carriers\RLCarriersRateRequestInterface;

/**
 * Provide freight shipping from R&L Carriers.
 *
 * @CommerceShippingMethod(
 *  id = "rlcarriers",
 *  label = @Translation("R&L Carriers")
 * )
 */
class RLCarriers extends ShippingMethodBase {
  /**
   * The UPS rate service.
   *
   * @var \Drupal\commerce_rl_carriers\RLCarriersRateRequestInterface
   */
  protected $rlcarriersRateService;

  /**
   * Constructs a new ShippingMethodBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\commerce_shipping\PackageTypeManagerInterface $packageTypeManager
   *   The package type manager.
   * @param \Drupal\commerce_rl_carriers\RLCarriersRateRequestInterface $rlcarriers_rate_request
   *   The rate request service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PackageTypeManagerInterface $packageTypeManager, RLCarriersRateRequestInterface $rlcarriers_rate_request) {
    // Rewrite the service keys to be integers.
    parent::__construct($configuration, $plugin_id, $plugin_definition, $packageTypeManager);
    $this->rlcarriersRateService = $rlcarriers_rate_request;
    $this->rlcarriersRateService->setConfig($configuration);
    $this->services['default'] = new ShippingService('default', $this->configuration['options']['rate_label']);
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
      $container->get('commerce_rl_carriers.rlcarriers_rate_request')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'api_information' => [
        'id' => '',
        'service_url' => 'https://www.rlcarriers.com/b2brateparam.asp',
      ],
      'options' => [
        'rate_label' => '',
      ],
      'services' => ['default'],
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
      '#description' => $this->isConfigured() ? $this->t('Update your RL Carriers API information.') : $this->t('Fill in your RL Carriers API information.'),
      '#weight' => $this->isConfigured() ? 10 : -10,
      '#open' => !$this->isConfigured(),
    ];

    $form['api_information']['id'] = [
      '#type' => 'textfield',
      '#title' => t('Account ID'),
      '#default_value' => $this->configuration['api_information']['id'],
      '#required' => TRUE,
    ];

    $form['api_information']['service_url'] = [
      '#type' => 'textfield',
      '#title' => t('Web service URL'),
      '#default_value' => $this->configuration['api_information']['service_url'],
      '#required' => TRUE,
    ];

    $form['options'] = [
      '#type' => 'details',
      '#title' => $this->t('Options'),
      '#description' => $this->isConfigured() ? $this->t('Update your RL Carriers options.') : $this->t('Fill in your RL Carriers options.'),
      '#weight' => $this->isConfigured() ? 10 : -10,
      '#open' => !$this->isConfigured(),
    ];

    $form['options']['rate_label'] = [
      '#type' => 'textfield',
      '#title' => t('Label'),
      '#default_value' => $this->configuration['options']['rate_label'],
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

      $this->configuration['api_information']['id'] = $values['api_information']['id'];
      $this->configuration['api_information']['service_url'] = $values['api_information']['service_url'];
      $this->configuration['options']['rate_label'] = $values['options']['rate_label'];

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
      $rates = $this->rlcarriersRateService->getRates($shipment, $this);
    }

    return $rates;
  }

  /**
   * Determine if we have the minimum information to connect to RL Carriers.
   *
   * @return bool
   *   TRUE if there is enough information to connect, FALSE otherwise.
   */
  protected function isConfigured() {
    $api_information = $this->configuration['api_information'];

    return (
      !empty($api_information['id'])
    );
  }

}
