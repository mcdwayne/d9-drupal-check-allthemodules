<?php

namespace Drupal\permutive\Plugin;

use Drupal\Component\Utility\NestedArray;

/**
 * Class PermutiveData.
 *
 * @package Drupal\permutive\Plugin
 */
class PermutiveData implements PermutiveDataInterface {

  /**
   * The data of the configuration object.
   *
   * @var array
   */
  protected $data = [];

  /**
   * The client type.
   *
   * @var string
   */
  protected $clientType;

  /**
   * {@inheritdoc}
   */
  public function getClientType() {
    return $this->clientType;
  }

  /**
   * {@inheritdoc}
   */
  public function setClientType($client_type) {
    $this->clientType = $client_type;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function get($key = '') {
    if (empty($key)) {
      return $this->data;
    }
    else {
      $parts = explode('.', $key);
      if (count($parts) == 1) {
        return isset($this->data[$key]) ? $this->data[$key] : NULL;
      }
      else {
        $value = NestedArray::getValue($this->data, $parts, $key_exists);
        return $key_exists ? $value : NULL;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getArray() {
    return $this->data;
  }

  /**
   * {@inheritdoc}
   */
  public function setData(array $data) {
    $this->validateKeys($data);
    $this->data = $data;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function set($key, $value) {
    // The dot/period is a reserved character; it may appear between keys, but
    // not within keys.
    if (is_array($value)) {
      $this->validateKeys($value);
    }
    $parts = explode('.', $key);
    if (count($parts) == 1) {
      $this->data[$key] = $value;
    }
    else {
      NestedArray::setValue($this->data, $parts, $value);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function clear($key) {
    $parts = explode('.', $key);
    if (count($parts) == 1) {
      unset($this->data[$key]);
    }
    else {
      NestedArray::unsetValue($this->data, $parts);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function merge(array $data_to_merge) {
    // Preserve integer keys so that keys are not changed.
    $this->setData(NestedArray::mergeDeepArray([$this->data, $data_to_merge], TRUE));
    return $this;
  }

  /**
   * Validates all keys in a passed in the array structure.
   *
   * @param array $data
   *   Data array structure.
   *
   * @throws \Drupal\Core\Config\ConfigValueException
   *   If any key in $data in any depth contains a dot.
   */
  protected function validateKeys(array $data) {
    foreach ($data as $key => $value) {
      if (strpos($key, '.') !== FALSE) {
        throw new PermutiveDataException("$key key contains a dot which is not supported.");
      }
      if (is_array($value)) {
        $this->validateKeys($value);
      }
    }
  }

}
