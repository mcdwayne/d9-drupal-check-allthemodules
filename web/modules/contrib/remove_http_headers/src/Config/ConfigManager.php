<?php

namespace Drupal\remove_http_headers\Config;

use Drupal\Core\Config\ConfigFactory;

/**
 * Manages module configuration.
 *
 * @package Drupal\remove_http_headers\Configuration
 */
class ConfigManager {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $config;

  /**
   * ConfigManager constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   *   The config factory service.
   */
  public function __construct(ConfigFactory $configFactory) {
    $this->config = $configFactory->getEditable('remove_http_headers.settings');
  }

  /**
   * Gets the HTTP headers that should be removed.
   *
   * @return string[]
   *   HTTP headers that should be removed.
   */
  public function getHeadersToRemove() {
    $headersToRemove = [];
    $headersToRemoveConfigData = $this->config->get('headers_to_remove');

    /* Return empty array if no headers are configured. */
    if (is_array($headersToRemoveConfigData)) {
      foreach ($headersToRemoveConfigData as $headerToRemove) {
        if (is_string($headerToRemove)) {
          $headersToRemove[] = $headerToRemove;
        }
      }
    }

    return $headersToRemove;
  }

  /**
   * Saves the HTTP headers that should be removed to the configuration.
   *
   * @param string[] $headersToRemove
   *   HTTP headers that should be removed.
   */
  public function saveHeadersToRemove(array $headersToRemove) {
    $this->config->set('headers_to_remove', $headersToRemove);
    $this->config->save();
  }

  /**
   * Whether or not the route with given name should be protected.
   *
   * @param string $headerName
   *   A HTTP header name.
   *
   * @return bool
   *   Should HTTP header with given name be removed.
   */
  public function shouldHeaderBeRemoved($headerName) {
    return in_array($headerName, $this->getHeadersToRemove());
  }

}
