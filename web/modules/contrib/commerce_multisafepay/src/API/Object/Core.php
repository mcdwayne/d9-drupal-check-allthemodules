<?php
 /**
 * Copyright © 2018 MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 */
namespace Drupal\commerce_multisafepay\API\Object;

use Drupal\commerce_multisafepay\Exceptions\ExceptionHelper;

class Core {

  protected $mspapi;
  public $result;

  public function __construct( \Drupal\commerce_multisafepay\API\Client $mspapi) {
    $this->mspapi = $mspapi;
  }

  public function post($body, $endpoint = 'orders') {
    $this->result = $this->processRequest('POST', $endpoint, $body);
    return $this->result;
  }

  public function patch($body, $endpoint = '') {
    $this->result = $this->processRequest('PATCH', $endpoint, $body);
    return $this->result;
  }

  public function getResult() {
    return $this->result;
  }

  public function get($endpoint, $id, $body = array(), $query_string = false) {
    if (!$query_string) {
      $url = "{$endpoint}/{$id}";
    } else {
      $url = "{$endpoint}?{$query_string}";
    }


    $this->result = $this->processRequest('GET', $url, $body);
    return $this->result;
  }

  protected function processRequest($http_method, $api_method, $http_body = NULL) {
    $body = $this->mspapi->processAPIRequest($http_method, $api_method, $http_body);
    $exceptionHelper = new ExceptionHelper();
    if (!($object = @json_decode($body))) {
      $exceptionHelper->PaymentGatewayException($body);
    }

    if (!empty($object->error_code)) {
        $exceptionHelper->PaymentGatewayException($object->error_info, $object->error_code);
    }
    return $object;
  }

}
