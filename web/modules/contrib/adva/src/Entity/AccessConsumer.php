<?php

namespace Drupal\adva\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines a Access consumer configuration entity class.
 *
 * @ConfigEntityType(
 *   id = "access_consumer",
 *   label = @Translation("Access Consumer"),
 *   fieldable = FALSE,
 *   admin_permission = "administer adva",
 *   entity_keys = {
 *     "id" = "id",
 *   },
 *   config_export = {
 *     "id",
 *     "settings",
 *     "providers",
 *     "provider_config",
 *   }
 * )
 */
class AccessConsumer extends ConfigEntityBase implements AccessConsumerInterface {

  /**
   * The ID of the consumer.
   *
   * @var string
   */
  protected $id;

  /**
   * The provider ids.
   *
   * @var array
   */
  protected $settings;

  /**
   * The general configuration.
   *
   * @var array
   */
  protected $providers;

  /**
   * The config for provider instances.
   *
   * @var array
   */
  protected $provider_config = [];

  /**
   * {@inheritdoc}
   */
  public function setProviders(array $providers) {
    $this->providers = $providers;
  }

  /**
   * {@inheritdoc}
   */
  public function setProviderConfig($provider_id, $config) {
    $this->provider_config[$provider_id] = $config;
  }

  /**
   * {@inheritdoc}
   */
  public function setSettings(array $settings) {
    $this->settings = $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getProviders() {
    return $this->providers;
  }

  /**
   * {@inheritdoc}
   */
  public function getProviderConfig($provider_id) {
    return $this->provider_config[$provider_id] ?: NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllProviderConfig() {
    return $this->provider_config;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings() {
    return $this->settings;
  }

}
