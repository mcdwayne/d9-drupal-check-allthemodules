<?php

namespace Drupal\instant_solr_index\Controller;

/**
 * @file
 * Solr server integration file for the instant_solr_index module.
 */

/**
 * Implements class to connect to the solr server.
 */
class InstantSolrIndexOptionManagedSolrServer {

  private $options;
  private $apiPath;
  private $managedSolrServiceId;
  private $managedSolrServerType;

  const OPTION_MANAGED_SOLR_SERVERS = 'Managed Solr Servers';
  const _CONFIG_EXTENSION_DIRECTORY = 'config_extension_directory';
  const _CONFIG_EXTENSION_CLASS_NAME = 'config_extension_class_name';
  const _CONFIG_PLUGIN_FUNCTION_NAME = 'config_plugin_function_name';
  const _CONFIG_EXTENSION_FILE_PATH = 'config_extension_file_path';
  const _CONFIG_EXTENSION_ADMIN_OPTIONS_FILE_PATH = 'config_extension_admin_options_file_path';
  const _CONFIG_OPTIONS = 'config_extension_options';
  const _CONFIG_OPTIONS_DATA = 'data';
  const _CONFIG_OPTIONS_IS_ACTIVE_FIELD_NAME = 'is_active_field';
  // Rest api orders channel property.
  const MANAGED_SOLR_SERVICE_CHANNEL_ORDER_URL = 'MANAGED_SOLR_SERVICE_CHANNEL_ORDER_URL';
  const MANAGED_SOLR_SERVICE_CHANNEL_GOOGLE_RECAPTCHA_TOKEN_URL = 'MANAGED_SOLR_SERVICE_CHANNEL_GOOGLE_RECAPTCHA_TOKEN_URL';
  const MANAGED_SOLR_SERVICE_LABEL = 'MANAGED_SOLR_SERVICE_LABEL';
  const MANAGED_SOLR_SERVICE_HOME_PAGE = 'MANAGED_SOLR_SERVICE_HOME_PAGE';
  const MANAGED_SOLR_SERVICE_API_PATH = 'MANAGED_SOLR_SERVICE_API_PATH';
  const MANAGED_SOLR_SERVICE_ORDERS_URLS = 'MANAGED_SOLR_SERVICE_ORDERS_URLS';
  const MANAGED_SOLR_SERVICE_ORDER_URL_BUTTON_LABEL = 'MANAGED_SOLR_SERVICE_ORDER_URL_BUTTON_LABEL';
  const MANAGED_SOLR_SERVICE_ORDER_URL_TEXT = 'MANAGED_SOLR_SERVICE_ORDER_URL_TEXT';
  const MANAGED_SOLR_SERVICE_ORDER_URL_LINK = 'MANAGED_SOLR_SERVICE_ORDER_URL_LINK';
  // Order link parameter indicating the current temporary index core to buy.
  const AVANGATE_ORDER_PARAMETER_ADDITIONAL_SOLR_INDEX_CORE = 'ADDITIONAL_SOLR_INDEX_CORE';

  /**
   * Implements constructor of the class.
   */
  public function __construct($managed_solr_service_id, $managed_solr_server_type) {
    $this->managedSolrServiceId = $managed_solr_service_id;
    $this->managedSolrServerType = $managed_solr_server_type;
  }

  /**
   * Creates google reCaptcha token.
   */
  public function callRestCreateGoogleRecaptchaToken() {

    $managed_solr_service = $this->getManagedSolrService();

    $response_object = $this->callRestPost(
            $managed_solr_service[self::MANAGED_SOLR_SERVICE_CHANNEL_GOOGLE_RECAPTCHA_TOKEN_URL]
    );

    return $response_object;
  }

  /**
   * Creates solr index.
   */
  public function callRestCreateSolrIndex($g_recaptcha_response) {

    $managed_solr_service = $this->getManagedSolrService();

    $response_object = $this->callRestPost(
            $managed_solr_service[self::MANAGED_SOLR_SERVICE_CHANNEL_ORDER_URL], array(
      'response' => $g_recaptcha_response,
      'remoteip' => \Drupal::request()->getClientIp(),
            )
    );
    return $response_object;
  }

  /**
   * Get the status of the solr index.
   */
  public function callRestGetTemporarySolrIndexStatus($solr_core) {

    $managed_solr_service = $this->getManagedSolrService();

    $response_object = $this->callRestGet(
            sprintf('%s/solr-cores/%s', $managed_solr_service[self::MANAGED_SOLR_SERVICE_CHANNEL_ORDER_URL], $solr_core)
    );

    return $response_object;
  }

  /**
   * Return managedSolrServiceId.
   */
  public function getManagedSolrService() {
    $managed_services = $this->getManagedSolrServices();

    return $managed_services[$this->managedSolrServiceId];
  }

  /**
   * Implements ManagedSolrServices.
   */
  public function getManagedSolrServices() {

    if ($this->managedSolrServerType == "searchApi") {
      $managed_solr_service_channel_order_url = 'https://api.gotosolr.com/v1/providers/8c25d2d6-54ae-4ff6-a478-e2c03f1e08a4/accounts/24b7729e-02dc-47d1-9c15-f1310098f93f/addons/80adb727-3ede-422a-b0b3-e3175ad72f00/order-solr-index/98e19a73-30d8-43ac-9cad-0cf811963df4';
      $managed_solr_service_channel_google_recaptcha_token_url = 'https://api.gotosolr.com/v1/providers/8c25d2d6-54ae-4ff6-a478-e2c03f1e08a4/accounts/24b7729e-02dc-47d1-9c15-f1310098f93f/addons/80adb727-3ede-422a-b0b3-e3175ad72f00/google-recaptcha-token';
    }
    elseif ($this->managedSolrServerType == "apache") {
      $managed_solr_service_channel_order_url = 'https://api.gotosolr.com/v1/providers/8c25d2d6-54ae-4ff6-a478-e2c03f1e08a4/accounts/24b7729e-02dc-47d1-9c15-f1310098f93f/addons/d9863f6f-8c3c-4d47-9f9f-abaab00106b5/order-solr-index/706db39f-a005-4d11-8d4a-96f203ee3318';
      $managed_solr_service_channel_google_recaptcha_token_url = 'https://api.gotosolr.com/v1/providers/8c25d2d6-54ae-4ff6-a478-e2c03f1e08a4/accounts/24b7729e-02dc-47d1-9c15-f1310098f93f/addons/d9863f6f-8c3c-4d47-9f9f-abaab00106b5/google-recaptcha-token';
    }
    $result = array();

    // Debug environment.
    $result['gotosolr'] = array(
      self::MANAGED_SOLR_SERVICE_LABEL => 'gotosolr.com',
      self::MANAGED_SOLR_SERVICE_HOME_PAGE => 'http://www.gotosolr.com/en',
      self::MANAGED_SOLR_SERVICE_API_PATH => 'https://api.gotosolr.com/v1/partners/24b7729e-02dc-47d1-9c15-f1310098f93f',
      self::MANAGED_SOLR_SERVICE_CHANNEL_ORDER_URL => $managed_solr_service_channel_order_url,
      self::MANAGED_SOLR_SERVICE_CHANNEL_GOOGLE_RECAPTCHA_TOKEN_URL => $managed_solr_service_channel_google_recaptcha_token_url,
      self::MANAGED_SOLR_SERVICE_ORDERS_URLS => array(
        array(
          self::MANAGED_SOLR_SERVICE_ORDER_URL_BUTTON_LABEL => 'Extend with a Yearly Plan',
          self::MANAGED_SOLR_SERVICE_ORDER_URL_TEXT => 'Yearly plan',
          self::MANAGED_SOLR_SERVICE_ORDER_URL_LINK => 'https://secure.avangate.com/order/checkout.php?PRODS=4642999&QTY=1&CART=1&CARD=1',
        ),
        array(
          self::MANAGED_SOLR_SERVICE_ORDER_URL_BUTTON_LABEL => 'Extend with a Monthly Plan',
          self::MANAGED_SOLR_SERVICE_ORDER_URL_TEXT => 'Monthly plan',
          self::MANAGED_SOLR_SERVICE_ORDER_URL_LINK => 'https://secure.avangate.com/order/checkout.php?PRODS=4653966&QTY=1&CART=1&CARD=1',
        ),
      ),
    );

    return $result;
  }

  /**
   * Http request using POST.
   */
  public function callRestPost($path, $data = array()) {

    $full_path = ('http' === substr($path, 0, 4)) ? $path : $this->apiPath . $path;

    // Json format.
    $headers = array('Content-Type' => 'application/json');

    // Pb with SSL certificate. Disabled.
    $context_options = array(
      'ssl' => array(
        'verify_peer' => FALSE,
      ),
    );
    $ssl_context = stream_context_create($context_options);

    // Drupal http request.
    $client = \Drupal::httpClient();
    $request = $client->post($full_path, [
      'json' => $data,
      'verify'=>false
    ]);
    $response = json_decode($request->getBody());
    if ('OK' != $response->status->state) {
      return (object) array('status' => (object) array('state' => 'ERROR', 'message' => $response->status->message));
    }

    return $response;
  }

  /**
   * Http request using GET.
   */
  public function callRestGet($path) {

    $full_path = ('http' === substr($path, 0, 4)) ? $path : $this->apiPath . $path . '&access_token=' . $this->getServiceOption('token');

    // Pb with SSL certificate. Disabled.
    $context_options = array(
      'ssl' => array(
        'verify_peer' => FALSE,
      ),
    );
    $ssl_context = stream_context_create($context_options);

    // Json format.
    $headers = array(
      'Content-Type' => 'application/json',
    );

    $client = \Drupal::httpClient();
    $request = $client->get($full_path, [
      'verify'=>false
    ]);
    $response = json_decode($request->getBody());
    if ('OK' != $response->status->state) {
      return (object) array('status' => (object) array('state' => 'ERROR', 'message' => $response->status->message));
    }

    return $response;
  }

  /**
   * Get a service option.
   *
   * @return bool
   *   Service option.
   */
  public function getServiceOption($option_name) {

    $service_options = $this->getServiceOptions();

    return (isset($service_options) && isset($service_options[$option_name])) ? $service_options[$option_name] : '';
  }

  /**
   * Get the options stored for a managed Solr service.
   *
   * @return string
   *   Option.
   */
  private function getServiceOptions() {

    return isset($this->options[$this->managedSolrServiceId]) ? $this->options[$this->managedSolrServiceId] : NULL;
  }

  /**
   * Check response state.
   */
  public static function isResponseOk($response_object) {

    return ('OK' === $response_object->status->state);
  }

  /**
   * Return response result.
   */
  public static function getResponseResult($response_object, $field) {

    return isset($response_object->results) && isset($response_object->results[0]) ? is_array($response_object->results[0]) ? $response_object->results[0][0]->$field : $response_object->results[0]->$field : NULL;
  }

  /**
   * Generate order urls.
   */
  public function generateConvertOrdersUrls($index_solr_core) {

    // Clone array.
    $generated_orders_urls = $this->getOrdersUrls();

    foreach ($generated_orders_urls as &$generated_order_url) {

      // Add the ADDITIONAL_SOLR_INDEX_CORE parameters to the order url.
      $generated_order_url[self::MANAGED_SOLR_SERVICE_ORDER_URL_LINK] .= sprintf(
              '&%s=%s', self::AVANGATE_ORDER_PARAMETER_ADDITIONAL_SOLR_INDEX_CORE, $index_solr_core);
    }

    return $generated_orders_urls;
  }

  /**
   * Get order urls.
   */
  public function getOrdersUrls() {
    $managed_service = $this->getManagedSolrService();

    return $managed_service[self::MANAGED_SOLR_SERVICE_ORDERS_URLS];
  }

}
