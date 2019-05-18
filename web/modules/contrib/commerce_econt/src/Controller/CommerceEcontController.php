<?php

namespace Drupal\commerce_econt\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Component\Serialization\Json;

/**
 * Controller implementation of ContainerInjectionInterface.
 *
 */
class CommerceEcontController implements ContainerInjectionInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * The econt configuration settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $configSettings;

  /**
   * The econt configuration db settings.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $configDbSettings;

  /**
   * The econt google maps api key.
   *
   * @var string
   */
  protected $googleMapsApiKey;

  /**
   * Constructs a new CommerceEcontController object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */

  public function __construct(RequestStack $request_stack) {
    $this->currentRequest = $request_stack->getCurrentRequest();
    $this->configSettings = \Drupal::config('commerce_econt.settings');
    $this->configDbSettings = \Drupal::database()
                              ->query('SELECT CONVERT(`plugin__target_plugin_configuration` USING utf8) `econt_db_data` 
                                              FROM `commerce_shipping_method_field_data` 
                                              WHERE `plugin__target_plugin_id`=\'econt\'')
                              ->fetchObject();
    $this->configDbSettings = unserialize($this->configDbSettings->econt_db_data);

    $this->googleMapsApiKey = $this->configSettings->get('commerce_econt_settings.google_maps_api_key');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack')
    );
  }

  public function ajaxValidateAddress() {


    if(empty($this->configDbSettings)) {
      echo Json::encode(['error' => true, 'message' => t('Econt Shipping processing error!')]);
      exit;
    }

    $config_mode = ($this->configDbSettings['econt_test_mode']) ? 'demo' : 'live';
    $service_url = $this->configSettings->get('commerce_econt_settings.' . $config_mode . '_service_url');

    $street_num_match = array();
    preg_match('/(.+)(\s)(\d+)/', $this->currentRequest->request->get('address_line1'), $street_num_match);
    $street_name = $street_num_match[1];
    $street_num = $street_num_match[3];

    $street_name = str_replace('Econt Office: ', '', $street_name);

    $store_data = [
      'locality' => $this->currentRequest->request->get('locality'),
      'postal_code' => $this->currentRequest->request->get('postal_code'),
      'address_line1' => $street_name,
      'address_line2' => $street_num,
    ];

    if($this->googleMapsApiKey) {
      $this->googleMapsApiTranslateAddress($store_data, $this->googleMapsApiKey);
    }

    $xmlStr = commerce_econt_check_store_addrr(
      $this->configDbSettings['econt_username'],
      $this->configDbSettings['econt_password'],
      $store_data
    );

    $response = commerce_econt_post_xml($service_url, $xmlStr);

    if($response['error']) {
      $response['message'] = t("The Address data do not match the requirements of Econt Shipping!");
    } else {
      $response['message'] = t("The Address data match the requirements of Econt Shipping!");
      $response['address_data'] = $store_data;
    }
    echo Json::encode($response);
    exit;
  }

  public function ajaxLoadOffices() {
    $xml_offices_list_file = $this->configSettings->get('commerce_econt_settings.xml_offices_list');
    $source_xml = simplexml_load_file(DRUPAL_ROOT . '/' .
                                              drupal_get_path('module', 'commerce_econt') .
                                              $xml_offices_list_file);

    $offices_data = [];
    if($this->googleMapsApiKey) {
      $locality = $this->googleMapsApiTranslateCity($this->currentRequest->request->get('locality'), $this->googleMapsApiKey);
    } else {
      $locality = $this->currentRequest->request->get('locality');
    }

    foreach($source_xml->offices->e as $office_obj) {
      if($office_obj->city_name == $locality || $office_obj->city_name_en == $locality) {

        $offices_data[] = [
          'locality' => $office_obj->city_name,
          'address_line1' => $office_obj->address_details->street_name . ' ' . $office_obj->address_details->num,
          'postal_code' => (string)$office_obj->post_code,
          'office_info' => t( 'Econt Office: ') . $office_obj->name . t(' Address: ') . $office_obj->address,
        ];
      }
    }

    $response = [];
    if(empty($offices_data)) {
      $response['error'] = true;
      $response['message'] = t("The City name do not match the requirements of Shipping to econt office!");
    } else {
      $response['locality'] = $locality;
      $response['offices'] = $offices_data;
    }

    echo Json::encode($response);
    exit;
  }

  private function googleMapsApiTranslateAddress(array &$store_data, $api_key) {
    $formatted_street = str_replace(' ', '+', $store_data['address_line1']);
    $google_url = 'https://maps.googleapis.com/maps/api/geocode/json?address=' . $formatted_street  . '+' . $store_data['address_line2'] . ',' . $store_data['postal_code'] . ',' . $store_data['locality'] . '&key=' . $api_key . '&language=BG';

    $ch = curl_init($google_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response_result = curl_exec($ch);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if(!$curl_error) {
      $response_result = json_decode($response_result, 1);
      if($response_result['status'] == 'OK') {
        $store_data['locality'] = $response_result['results'][0]['address_components'][2]['long_name'];
        $store_data['postal_code'] = $response_result['results'][0]['address_components'][5]['long_name'];
        $store_data['address_line1'] = str_replace(['„', "“"], ['', ''], $response_result['results'][0]['address_components'][1]['short_name']);
        $store_data['address_line2'] = $response_result['results'][0]['address_components'][0]['long_name'];
      }
    }
  }

  private function googleMapsApiTranslateCity($locality, $api_key) {
    $google_url = 'https://maps.googleapis.com/maps/api/geocode/json?address=' . $locality . '&key=' . $api_key . '&language=BG';

    $ch = curl_init($google_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response_result = curl_exec($ch);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if(!$curl_error) {
      $response_result = json_decode($response_result, 1);
      if($response_result['status'] == 'OK') {
        $locality = $response_result['results'][0]['address_components'][0]['long_name'];
      }
    }

    return $locality;
  }

  public function storeEcontOffices() {
    try {
      if(empty($this->configDbSettings)) {
        throw new \Exception(t('Econt Shipping processing error!'));
      }
    } catch (\Exception $exception) {
      _drupal_exception_handler($exception);
    }
    $config_mode = ($this->configDbSettings['econt_test_mode']) ? 'demo' : 'live';
    $service_url = $this->configSettings->get('commerce_econt_settings.' . $config_mode . '_service_url');

    $response = commerce_econt_get_offices(
      $this->configDbSettings['econt_username'],
      $this->configDbSettings['econt_password'],
        $service_url
    );

    try {
      if($response['error']) {
        throw new \Exception($response['message']);
      } else {
        $xml_offices_list_file = $this->configSettings->get('commerce_econt_settings.xml_offices_list');
        file_unmanaged_save_data(
          $response['xml_result'],
          DRUPAL_ROOT . '/' . drupal_get_path('module', 'commerce_econt') . $xml_offices_list_file,
          FILE_EXISTS_REPLACE
          );
      }
    } catch (\Exception $exception) {
      _drupal_exception_handler($exception);
    }

    echo 'The Econt offices list is updated correctly';
    exit;
  }
}