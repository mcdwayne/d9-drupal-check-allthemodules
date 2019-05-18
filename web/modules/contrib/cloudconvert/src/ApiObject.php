<?php

namespace Drupal\cloudconvert;

use Drupal\cloudconvert\Exceptions\InvalidParameterException;

/**
 * Base class for Objects returned from the CloudConvert API.
 */
class ApiObject {

  /**
   * URL parameter.
   *
   * @var string
   */
  public $url;

  /**
   * Contains the API object.
   *
   * @var \Drupal\cloudconvert\Api
   */
  protected $api;

  /**
   * Contains the object data returned from the CloudConvert API.
   *
   * @var array
   */
  protected $data = [];

  /**
   * Construct a new ApiObject instance.
   *
   * @param Api $api
   *   Cloud Convert API.
   * @param string $url
   *   The Object URL.
   *
   * @throws Exceptions\InvalidParameterException
   */
  public function __construct(Api $api, $url) {
    if ($url === NULL) {
      throw new InvalidParameterException('Object URL parameter is not set');
    }
    $this->api = $api;
    $this->url = $url;
  }

  /**
   * Refresh Object Data.
   *
   * @param \Drupal\cloudconvert\Parameters $parameters
   *   Parameters for refreshing the Object.
   *
   * @return \Drupal\cloudconvert\ApiObject
   *   Cloud Convert API Object.
   *
   * @throws \Drupal\cloudconvert\Exceptions\ApiTemporaryUnavailableException
   * @throws \Drupal\cloudconvert\Exceptions\ApiException
   * @throws \Drupal\cloudconvert\Exceptions\ApiConversionFailedException
   * @throws \Drupal\cloudconvert\Exceptions\ApiBadRequestException
   * @throws \Drupal\cloudconvert\Exceptions\InvalidParameterException
   */
  public function refresh(Parameters $parameters = NULL) {
    if ($parameters === NULL) {
      $parameters = new Parameters([]);
    }
    $this->data = $this->api->get($this->url, $parameters->getParameters(), FALSE);
    return $this;
  }

  /**
   * Get the data array.
   *
   * @return array
   *   Data.
   */
  public function getData() {
    return $this->data;
  }

  /**
   * Get the name of the object.
   *
   * @param string $name
   *   Name.
   *
   * @return null|object
   *   Data.
   */
  public function get($name) {
    return $this->__get($name);
  }

  /**
   * Access Object data via $object->prop->subprop.
   *
   * @param string $name
   *   Name.
   *
   * @return null|object
   *   Data.
   */
  public function __get($name) {

    if (!\is_array($this->data) || !array_key_exists($name, $this->data)) {
      return NULL;
    }

    if (\is_object($this->data[$name])) {
      return $this->data[$name];
    }

    return self::arrayToObject($this->data[$name]);
  }

  /**
   * Converts multi dimensional arrays into objects.
   *
   * @param array|object $data
   *   Data.
   *
   * @return object
   *   Data.
   */
  private static function arrayToObject($data) {
    if (\is_array($data)) {
      /*
       * Return array converted to object
       * Using [__CLASS__, __METHOD__] (Magic constant)
       * for recursive call
       */
      return (object) array_map([__CLASS__, __METHOD__], $data);
    }
    return $data;
  }

}
