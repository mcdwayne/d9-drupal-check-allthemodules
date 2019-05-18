<?php

namespace Drupal\commerce_econt\Plugin\Commerce\ShippingMethod;

use Drupal\commerce_price\Price;
use Drupal\commerce_shipping\Entity\ShipmentInterface;
use Drupal\commerce_shipping\PackageTypeManagerInterface;
use Drupal\commerce_shipping\ShippingRate;
use Drupal\commerce_shipping\ShippingService;
use Drupal\Core\Form\FormStateInterface;


/**
 * Provides the Econt shipping method.
 *
 * @CommerceShippingMethod(
 *   id = "econt",
 *   label = @Translation("Econt Shipping"),
 * )
 */
class Econt extends ShippingMethodEcont {

  /**
   * Constructs a new Econt object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\commerce_shipping\PackageTypeManagerInterface $package_type_manager
   *   The package type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PackageTypeManagerInterface $package_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $package_type_manager);

    $this->services['default'] = new ShippingService('default', $this->configuration['econt_label']);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'rate_label' => NULL,
      'rate_amount' => NULL,
      'services' => ['default'],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $amount = $this->configuration['rate_amount'];
    // A bug in the plugin_select form element causes $amount to be incomplete.
    if (isset($amount) && !isset($amount['number'], $amount['currency_code'])) {
      $amount = NULL;
    }

    $form['econt_test_mode'] = [
      '#type' => 'radios',
      '#title' => t('Econt Test mode'),
      '#default_value' => (isset($this->configuration['econt_mode'])) ? $this->configuration['econt_mode'] : 1,
      '#options' => [1 => t('Yes'), 0 => t('No')],
      '#required' => TRUE,
    ];

    $form['econt_label'] = [
      '#type' => 'textfield',
      '#title' => t('Econt label'),
      '#description' => t('Shown to customers during checkout.'),
      '#default_value' => ($this->configuration['econt_label']) ? $this->configuration['econt_label'] : '',
      '#required' => TRUE,
    ];

    $form['econt_username'] = [
      '#type' => 'textfield',
      '#title' => t('Econt Username'),
      '#description' => t('Your Econt API username'),
      '#default_value' => ($this->configuration['econt_username']) ? $this->configuration['econt_username'] : '',
      '#required' => TRUE,
    ];

    $form['econt_password'] = [
      '#type' => 'textfield',
      '#title' => t('Econt password'),
      '#description' => t('Your Econt API password'),
      '#default_value' => ($this->configuration['econt_password']) ? $this->configuration['econt_password'] : '',
      '#required' => TRUE,
    ];

    $form['econt_emp_name'] = [
      '#type' => 'textfield',
      '#title' => t('Shipment employee names'),
      '#description' => t('The names of the employee who is responsible for Econt Shipping'),
      '#default_value' => ($this->configuration['econt_emp_name']) ? $this->configuration['econt_emp_name'] : '',
      '#required' => TRUE,
    ];

    $form['econt_emp_phone'] = [
      '#type' => 'tel',
      '#title' => t('Shipment employee phone'),
      '#description' => t('The phone of the employee who is responsible for Econt Shipping'),
      '#default_value' => ($this->configuration['econt_emp_phone']) ? $this->configuration['econt_emp_phone'] : '',
      '#required' => TRUE,
    ];  

    $form['econt_minimun_rate'] = [
      '#type' => 'commerce_price',
      '#title' => t('Econt minimum rate'),
      '#default_value' => ($this->configuration['econt_minimun_rate']) ? $this->configuration['econt_minimun_rate']: ['number'=> '', 'currency_code' => ''],
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);

    $config = \Drupal::config('commerce_econt.settings');
    $config_mode = ($values['econt_test_mode']) ? 'demo' : 'live';
    $service_url = $config->get('commerce_econt_settings.' . $config_mode . '_service_url');

    $xmlStr = commerce_econt_check_store_addrr(
                $values['econt_username'],
                $values['econt_password'],
                $this->getDefaultStoreData()
              );

    $response = commerce_econt_post_xml($service_url, $xmlStr);
    if($response['error']) {
      $form_state->setErrorByName('econt_label', $response['message']);
    }

  }
  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['econt_test_mode'] = $values['econt_test_mode'];
      $this->configuration['econt_label'] = $values['econt_label'];
      $this->configuration['econt_username'] = $values['econt_username'];
      $this->configuration['econt_password'] = $values['econt_password'];
      $this->configuration['econt_emp_name'] = $values['econt_emp_name'];
      $this->configuration['econt_emp_phone'] = $values['econt_emp_phone'];
      $this->configuration['econt_minimun_rate'] = $values['econt_minimun_rate'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function calculateRates(ShipmentInterface $shipment) {
    // Rate IDs aren't used in a flat rate scenario because there's always a
    // single rate per plugin, and there's no support for purchasing rates.

    $config = \Drupal::config('commerce_econt.settings');
    $config_mode = ($this->configuration['econt_test_mode']) ? 'demo' : 'live';
    $request_url = $config->get('commerce_econt_settings.' . $config_mode . '_url');

    $payment_gateway = $shipment->getOrder()->get('payment_gateway')->entity->getPlugin();
    $payment_method_name = $payment_gateway->getLabel();
    $is_cod = ($payment_method_name == $config->get('commerce_econt_settings.cod_payment_name')) ? true : false ;

    $requestXml = commerce_econt_calculate_delivery_xml(
      $this->getDefaultStoreData(),
      $this->configuration,
      $this->getShippingData($shipment),
      $is_cod
    );
    $response_data = commerce_econt_send_request_xml($request_url, $requestXml);
    $response_msg = '';
    $amount = $this->configuration['econt_minimun_rate'];

    if(!$response_data['error']) {
      $amount['number'] = $response_data['econt_amount_data'];
    }

    $rate_id = 0;
    $amount = new Price($amount['number'], $amount['currency_code']);
    
    $rates = [];

    $rates[] = new ShippingRate($rate_id, $this->services['default'], $amount, NULL, $response_msg);

    return $rates;
  }
}
