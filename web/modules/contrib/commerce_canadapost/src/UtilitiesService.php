<?php

namespace Drupal\commerce_canadapost;

use Drupal\commerce_canadapost\Plugin\Commerce\ShippingMethod\CanadaPost;
use Drupal\commerce_shipping\Entity\ShipmentInterface;
use Drupal\commerce_store\Entity\StoreInterface;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

use Exception;

/**
 * Class UtilitiesService.
 *
 * Contains helper functions for the Canada Post module.
 *
 * @package Drupal\commerce_canadapost
 */
class UtilitiesService {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a UtilitiesService class.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Build the form fields for the Canada Post API settings.
   *
   * @param \Drupal\commerce_store\Entity\StoreInterface $store
   *   A store entity, if the api settings are for a store.
   * @param \Drupal\commerce_canadapost\Plugin\Commerce\ShippingMethod\CanadaPost $shipping_method
   *   The Canada Post shipping method.
   *
   * @return array
   *   An array of form fields.
   *
   * @see \Drupal\commerce_canadapost\Form::buildForm()
   * @see \commerce_canadapost_form_alter()
   */
  public function buildApiForm(StoreInterface $store = NULL, CanadaPost $shipping_method = NULL) {
    $form = [];

    // Fetch the Canada Post API settings.
    $api_settings = $this->getApiSettings($store, $shipping_method);

    $form['commerce_canadapost_api'] = [
      '#type' => 'details',
      '#title' => $this->t('Canada Post API authentication'),
      '#open' => TRUE,
    ];

    // If we are in the store form, display an option to set store-wide
    // settings.
    $checkbox_default_value = 0;
    if ($store) {
      $title = $this->t('Set store-wide Canada Post API settings');
      $description = $this->t('The API settings defined here will be used by @url that do not define their own API settings.
        <br>Leave this box unchecked if your store does not use Canada Post, or if you will be defining the API settings for each individual shipping method.', [
          '@url' => Link::fromTextAndUrl(
            $this->t('shipping methods'),
            Url::fromRoute('entity.commerce_shipping_method.collection')
          )->toString(),
        ]
      );

      // If the API settings are set on the store, set the default value to 1.
      if (!empty($store->get('canadapost_api_settings')->getValue()[0]['value'])) {
        $checkbox_default_value = 1;
      }
    }
    // Else, if we are in the shipping method page display an option to set
    // shipping method settings.
    else {
      $title = $this->t('Override Canada Post store API settings');
      $description = $this->t('Leave this box unchecked if you\'d like to use @url when fetching rates and tracking details.', [
        '@url' => Link::fromTextAndUrl(
          $this->t('the store API settings'),
          Url::fromRoute('entity.commerce_store.collection')
        )->toString(),
      ]);

      // If the API settings are set on the method, set the default value to 1.
      if ($shipping_method->apiIsConfigured()) {
        $checkbox_default_value = 1;
      }
    }

    $form['commerce_canadapost_api']['commerce_canadapost_store_settings'] = [
      '#type' => 'checkbox',
      '#title' => $title,
      '#description' => $description,
      '#default_value' => $checkbox_default_value,
    ];

    $form['commerce_canadapost_api']['commerce_canadapost_customer_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Customer number'),
      '#default_value' => $api_settings ? $api_settings['customer_number'] : '',
      '#required' => TRUE,
    ];
    $form['commerce_canadapost_api']['commerce_canadapost_username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#default_value' => $api_settings ? $api_settings['username'] : '',
      '#required' => TRUE,
    ];
    $form['commerce_canadapost_api']['commerce_canadapost_password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Password'),
      '#default_value' => $api_settings ? $api_settings['password'] : '',
      '#required' => TRUE,
    ];
    $form['commerce_canadapost_api']['commerce_canadapost_contract_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Contract ID'),
      '#default_value' => $api_settings ? $api_settings['contract_id'] : '',
    ];
    $form['commerce_canadapost_api']['commerce_canadapost_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Mode'),
      '#default_value' => $api_settings ? $api_settings['mode'] : '',
      '#options' => [
        'test' => $this->t('Test'),
        'live' => $this->t('Live'),
      ],
      '#required' => TRUE,
    ];
    $form['commerce_canadapost_api']['commerce_canadapost_log'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Log the following messages for debugging'),
      '#options' => [
        'request' => $this->t('API request messages'),
        'response' => $this->t('API response messages'),
      ],
      '#default_value' => $api_settings ? $api_settings['log'] : [],
    ];

    $this->alterApiFormFields($form, $store);

    return $form;
  }

  /**
   * Decode the Canada Post API settings stored as json in the store entity.
   *
   * @param object $api_settings
   *   The json encoded Canada Post api settings.
   *
   * @return array
   *   An array of values extracted from the json object.
   */
  public function decodeSettings($api_settings) {
    return json_decode($api_settings, TRUE);
  }

  /**
   * Encode the Canada Post API settings values in a json object.
   *
   * @param array $values
   *   The form_state values with the Canada Post API settings.
   *
   * @return object
   *   The encoded json object.
   */
  public function encodeSettings(array $values) {
    foreach ($this->getApiKeys() as $key) {
      $api_settings_values[$key] = $values["commerce_canadapost_$key"];
    }

    return json_encode($api_settings_values);
  }

  /**
   * Fetch all incomplete Canada Post shipments that have a tracking pin.
   *
   * @param array $order_ids
   *   Only fetch shipments of specific order IDs.
   *
   * @return array
   *   An array of shipment entities.
   */
  public function fetchShipmentsForTracking(array $order_ids = NULL) {
    // Query the db for the incomplete shipments.
    $shipment_query = $this->entityTypeManager
      ->getStorage('commerce_shipment')
      ->getQuery();
    $shipment_query
      ->condition('type', 'canadapost')
      ->condition('state', 'completed', '!=')
      ->condition('tracking_code', NULL, 'IS NOT NULL');
    // If specific order IDs have been passed.
    if (!empty($order_ids)) {
      $shipment_query->condition('order_id', $order_ids, 'IN');
    }
    // Fetch the results.
    $shipment_ids = $shipment_query->execute();

    // Return the loaded shipment entities.
    return $this->entityTypeManager
      ->getStorage('commerce_shipment')
      ->loadMultiple($shipment_ids);
  }

  /**
   * Return the Canada Post API keys.
   *
   * @return array
   *   An array of API setting keys.
   */
  public function getApiKeys() {
    return [
      'customer_number',
      'username',
      'password',
      'contract_id',
      'mode',
      'log',
    ];
  }

  /**
   * Fetch the Canada Post API settings, first from the method, then, the store.
   *
   * @param \Drupal\commerce_store\Entity\StoreInterface $store
   *   A store entity, if the api settings are for a store.
   * @param \Drupal\commerce_canadapost\Plugin\Commerce\ShippingMethod\CanadaPost $shipping_method
   *   The shipping method.
   *
   * @throws \Exception
   *
   * @return array
   *   Returns the api settings.
   */
  public function getApiSettings(
    StoreInterface $store = NULL,
    CanadaPost $shipping_method = NULL
  ) {
    $api_settings = [];

    if (!$store && !$shipping_method) {
      throw new Exception('A shipping method or a store is required to fetch the Canada Post API settings.');
    }

    // Check if we have settings set on the shipping method, if so, use that.
    if ($shipping_method && $shipping_method->apiIsConfigured()) {
      $api_settings = $shipping_method->getConfiguration()['api'];
    }
    // Else, we fallback to the store API settings.
    elseif ($store && !empty($store->get('canadapost_api_settings')->getValue()[0]['value'])) {
      $api_settings = $this->decodeSettings(
        $store->get('canadapost_api_settings')->getValue()[0]['value']
      );
    }

    return $api_settings;
  }

  /**
   * Hide the Canada Post tracking fields from the checkout form.
   *
   * @param array $form
   *   The form array.
   */
  public function hideTrackingFields(array &$form) {
    foreach ($form['shipping_information']['shipments'] as &$shipment) {
      $fields = [
        'canadapost_actual_delivery',
        'canadapost_attempted_delivery',
        'canadapost_expected_delivery',
        'canadapost_mailed_on',
        'canadapost_current_location',
      ];

      foreach ($fields as $field) {
        if (!isset($shipment[$field])) {
          continue;
        }

        $shipment[$field]['#access'] = FALSE;
      }
    }
  }

  /**
   * Update the shipment fields with the tracking summary.
   *
   * @param \Drupal\commerce_shipping\Entity\ShipmentInterface $shipment
   *   The commerce shipment.
   * @param array $tracking_summary
   *   The tracking summary from Canada Post.
   *
   * @return int
   *   The order ID for which the shipment was updated for.
   */
  public function updateTrackingFields(ShipmentInterface $shipment, array $tracking_summary) {
    // Update the fields.
    if ($tracking_summary['actual-delivery-date'] != '') {
      $shipment->set('canadapost_actual_delivery', $tracking_summary['actual-delivery-date']);
    }

    if ($tracking_summary['attempted-date'] != '') {
      $shipment->set('canadapost_attempted_delivery', $tracking_summary['attempted-date']);
    }

    if ($tracking_summary['expected-delivery-date'] != '') {
      $shipment->set('canadapost_expected_delivery', $tracking_summary['expected-delivery-date']);
    }

    if ($tracking_summary['mailed-on-date'] != '') {
      $shipment->set('canadapost_mailed_on', $tracking_summary['mailed-on-date']);
    }

    if ($tracking_summary['event-location'] != '') {
      $shipment->set('canadapost_current_location', $tracking_summary['event-location']);
    }

    // Now, save the shipment.
    $shipment->save();

    // Return the order ID for this updated shipment.
    return $shipment->getOrderId();
  }

  /**
   * Alter the Canada Post API settings form fields.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\commerce_store\Entity\StoreInterface $store
   *   A store entity, if the api settings are for a store.
   */
  protected function alterApiFormFields(array &$form, StoreInterface $store = NULL) {
    // Fields should be visible only if the store_settings checkbox is checked.
    // The input name is different in the shipping method form, so detect where
    // we are and change accordingly.
    $field_input_name = 'commerce_canadapost_store_settings';
    if (!$store) {
      $field_input_name = 'plugin[0][target_plugin_configuration][canadapost][commerce_canadapost_api][commerce_canadapost_store_settings]';
    }

    $states = [
      'visible' => [
        ':input[name="' . $field_input_name . '"]' => [
          'checked' => TRUE,
        ],
      ],
      'required' => [
        ':input[name="' . $field_input_name . '"]' => [
          'checked' => TRUE,
        ],
      ],
    ];
    foreach ($this->getApiKeys() as $key) {
      $form['commerce_canadapost_api']["commerce_canadapost_$key"]['#states'] = $states;
      $form['commerce_canadapost_api']["commerce_canadapost_$key"]['#required'] = FALSE;
    }

    // Contract ID and Log are not required so remove it from the states as
    // well.
    unset($form['commerce_canadapost_api']['commerce_canadapost_contract_id']['#states']['required']);
    unset($form['commerce_canadapost_api']['commerce_canadapost_log']['#states']['required']);
  }

}
