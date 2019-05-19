<?php

namespace Drupal\virtual_entities\Plugin;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for Virtual entity storage client plugins.
 */
abstract class VirtualEntityStorageClientPluginBase extends PluginBase implements VirtualEntityStorageClientPluginInterface {

  /**
   * The HTTP client to fetch the data with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The decoder to decode the data.
   *
   * @var \Drupal\virtual_entities\VirtualEntityDecoderService
   */
  protected $decoder;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    // Set default guzzle http client.
    if (isset($this->configuration['http_client'])) {
      $this->httpClient = $this->configuration['http_client'];
    }
    else {
      $this->httpClient = \Drupal::httpClient();
    }

    // Set decoder.
    if (isset($this->configuration['decoder'])) {
      $this->decoder = $this->configuration['decoder'];
    }
    else {
      // Load decoder from Drupal service.
      $this->decoder = \Drupal::service('virtual_entity.storage_client.decoder');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginLabel() {
    if (isset($this->pluginDefinition['label'])) {
      return $this->pluginDefinition['label'];
    }
    else {
      return NULL;
    }
  }

}
