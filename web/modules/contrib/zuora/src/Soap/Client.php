<?php

namespace Drupal\zuora\Soap;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\zuora\Exception\ZuoraException;
use Drupal\zuora\ZuoraClientBase;

class Client extends ZuoraClientBase {

  /**
   *
   */
  const ZUORA_SOAP_PRODUCTION = 'https://www.zuora.com/apps/services/a/80.0';

  /**
   *
   */
  const ZUORA_SOAP_SANDBOX = 'https://apisandbox.zuora.com/apps/services/a/80.0';

  /**
   * Generic namespace.
   */
  const TYPE_NAMESPACE = 'http://api.zuora.com/';

  /**
   * Defines the Zuora Object namespace, which is required for certain nodes in
   * the request.
   */
  const TYPE_OBJECT = 'http://object.api.zuora.com/';

  protected $wsdl;
  protected $client;
  protected $headers = [];

  /**
   * Sets the API credential information.
   *
   * @throws ZuoraException
   *
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);

    $this->wsdl = $this->zuoraConfig->get('wsdl');

    $this->client = new \SoapClient($this->wsdl, array(
      'soap_version' => SOAP_1_1,
      'trace' => 1,
    ));
    $this->setLocation();
  }

  protected function setLocation() {
    $location = ($this->isSandboxed()) ? self::ZUORA_SOAP_SANDBOX : self::ZUORA_SOAP_PRODUCTION;
    $this->client->__setLocation($location);
  }

  protected function createSession() {
    try {
      $result = $this->client->login(array(
        'username' => $this->zuoraConfig->get('access_key_id'),
        'password' => $this->zuoraConfig->get('access_secret_key'),
      ));
    }
    catch (\SoapFault $e) {
      throw new ZuoraException('Error authenticating to remote service');
    }

    $this->addHeader('session', new \SoapHeader(
      'http://api.zuora.com/',
      'SessionHeader',
      ['session' => $result->result->Session]
    ));
  }

  public function addHeader($name, \SoapHeader $soap_header) {
    $this->headers[$name] = $soap_header;
  }

  public function query($query) {
    $api_query = [
      'query' => [
        'queryString' => $query,
      ],
    ];

    try {
      $result = $this->call('query', $api_query);
    }
    catch (\SoapFault $e) {
      throw new ZuoraException('Error executing remote API call: ' . $e->getMessage());
    }

    return $result;
  }

  /**
   * @param $function_name
   * @param array $arguments
   * @param array|NULL $options
   * @param array|NULL $input_headers
   * @param array|NULL $output_headers
   * @return mixed
   * @throws \SoapFault
   */
  public function call($function_name, array $arguments, array $options = [], $input_headers = [], array &$output_headers = []) {
    if (!isset($this->headers['session'])) {
      $this->createSession();
    }
    $input_headers += $this->headers;
    return $this->client->__soapCall($function_name, $arguments, $options, $input_headers, $output_headers);
  }

  /**
   * Process a subscribe call with the Soap client.
   *
   * @param $arguments array
   *   An array of an array of SoapVars of type 'subscribes'. It's like this
   *   because you pass in an array (which has all of the arguments to the call
   *   in it) and then another array inside of that which stores the subscribes.
   * @return object
   * @throws \SoapFault
   */
  public function subscribe($arguments) {
    return $this->call('subscribe', $arguments);
  }

  /**
   * Process a amend call with the Soap client.
   *
   * @param $arguments
   * @return mixed
   */
  public function amend($arguments){
    return $this->call('amend', $arguments);
  }

  /**
   * Process a create call with the Soap client.
   *
   * @param $arguments array
   *   An array of zObjects
   * @return object
   * @throws \SoapFault
   */
  public function create($arguments) {
    return $this->call('create', $arguments);
  }

  /**
   * Process a delete call with the Soap client.
   *
   * @param $arguments array
   *   An array of arguments.
   * @return object
   * @throws \SoapFault
   */
  public function delete($arguments) {
    return $this->call('delete', $arguments);
  }

  /**
   * Process an update call with the Soap client.
   *
   * @param $arguments array
   *   An array of zObjects
   * @return object
   * @throws \SoapFault
   */
  public function update($arguments) {
    return $this->call('update', $arguments);
  }

  /**
   * Returns the body of the last request this class sent.
   * @return string
   */
  public function lastRequest() {
    return $this->client->__getLastRequest();
  }

  /**
   * Returns the last response headers this class sent.
   * @return string
   */
  public function lastResponseHeaders(){
    return $this->client->__getLastResponseHeaders();
  }

  /**
   * Returns the last request headers this class sent.
   * @return string
   */
  public function lastRequestHeaders(){
    return $this->client->__getLastRequestHeaders();
  }

  /**
   *Returns the body of the last response this class sent.
   * @return string
   */
  public function lastResponse() {
    return $this->client->__getLastResponse();
  }
}
