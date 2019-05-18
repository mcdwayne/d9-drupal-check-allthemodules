<?php

namespace Drupal\search_api_swiftype;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\search_api_swiftype\SwiftypeClient\SwiftypeClientInterface;

/**
 * Defines the abstract Swiftype entity.
 */
abstract class SwiftypeEntity implements SwiftypeEntityInterface {

  use StringTranslationTrait;

  /**
   * The client service to communicate with Swiftype.
   *
   * @var \Drupal\search_api_swiftype\SwiftypeClient\SwiftypeClientInterface
   */
  protected $clientService;

  /**
   * Object holding the entity data.
   *
   * @var object
   */
  protected $data;

  /**
   * Constructs a Swiftype entity.
   *
   * @param \Drupal\search_api_swiftype\Drupal\search_api_swiftype\SwiftypeClient\SwiftypeClientInterface $client_service
   *   The client service.
   */
  public function __construct(SwiftypeClientInterface $client_service) {
    $this->clientService = $client_service;
  }

  /**
   * Magic getter callback.
   *
   * @param string $name
   *   Name of property.
   *
   * @return mixed
   *   The property value.
   */
  public function __get($name) {
    return isset($this->data[$name]) ? $this->data[$name] : NULL;
  }

  /**
   * Magic setter callback.
   *
   * @param string $name
   *   Name of property.
   * @param mixed $value
   *   Value of property to set.
   */
  public function __set($name, $value) {
    $this->data[$name] = $value;
  }

  /**
   * Check if a property exists.
   *
   * @param string $name
   *   Name of property.
   *
   * @return bool
   *   TRUE if the property exists, FALSE otherwise.
   */
  public function __isset($name) {
    return isset($this->data[$name]);
  }

  /**
   * {@inheritdoc}
   */
  public function getClientService() {
    return $this->clientService;
  }

  /**
   * {@inheritdoc}
   */
  public function getRawData() {
    return $this->data;
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->data['id'];
  }

}
