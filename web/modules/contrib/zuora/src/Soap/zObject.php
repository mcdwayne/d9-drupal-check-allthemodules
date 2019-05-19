<?php

namespace Drupal\zuora\Soap;

abstract class zObject {

  protected $zType = 'zObject';

  protected $_data = [];

  protected $zNamespace = 'http://object.api.zuora.com/';

  public function __construct(array $values = []) {
    $this->_data = $values;
  }

  /**
   *
   * @param $name string
   * @param $value mixed
   * @return void
   */
  public function __set($name, $value) {
    $this->_data[$name] = $value;
  }

  public function &__get($name) {
    $data = $this->_data[$name];
    return $data;
  }

  public function __isset($name) {
    return isset($this->_data[$name]);
  }

  protected function getData() {
    ksort($this->_data);
    return array_filter($this->_data, function ($value) {
      return $value !== NULL;
    });
  }

  public function getSoapVar() {
    return new \SoapVar($this->getData(), SOAP_ENC_OBJECT, $this->zType, $this->zNamespace);
  }

}
