<?php

declare(strict_types = 1);

namespace Drupal\sendwithus;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\key\KeyRepository;
use sendwithus\API;

/**
 * Provides a service to manage Sendwithus API.
 */
class ApiManager {

  /**
   * Drupal\key\KeyRepository definition.
   *
   * @var \Drupal\key\KeyRepository
   */
  protected $keyRepository;

  /**
   * The configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\key\KeyRepository $key_repository
   *   The key repository.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(KeyRepository $key_repository, ConfigFactoryInterface $config_factory) {
    $this->keyRepository = $key_repository;
    $this->config = $config_factory->getEditable('sendwithus.settings');
  }

  /**
   * Gets the api key name.
   *
   * @return null|string
   *   The key name.
   */
  protected function getKeyName() : ? string {
    return $this->config->get('api_key');
  }

  /**
   * Sets the api key.
   *
   * @param string $key
   *   The api key.
   */
  public function setApiKey(string $key) : void {
    $this->config->set('api_key', $key)
      ->save();
  }

  /**
   * Gets the api key.
   *
   * @return string
   *   The api key.
   */
  public function getApiKey() : string {
    if (!$this->getKeyName()) {
      return '';
    }
    return $this->keyRepository->getKey($this->getKeyName())
      ->getKeyValue();
  }

  /**
   * Gets a new API instance.
   *
   * @param array $options
   *   The options.
   *
   * @return \sendwithus\API
   *   The api instance.
   */
  public function getAdapter(array $options = []) : API {
    if (!isset($options['adapter'])) {
      return new API($this->getApiKey(), $options);
    }
    if (!$options['adapter'] instanceof API) {
      throw new \InvalidArgumentException('Invalid adapter');
    }
    return $options['adapter'];
  }

}
