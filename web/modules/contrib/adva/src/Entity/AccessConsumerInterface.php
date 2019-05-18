<?php

namespace Drupal\adva\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining Advanced Access Consumer Config Entity.
 */
interface AccessConsumerInterface extends ConfigEntityInterface {

  /**
   * Set AccessProvider Plugin ids.
   *
   * @param string[] $providers
   *   List of active providers for instance.
   */
  public function setProviders(array $providers);

  /**
   * Update config for a provider.
   *
   * @param string $provider_id
   *   Id of AccessProvider plugin.
   * @param mixed $config
   *   Config for the provider instance.
   */
  public function setProviderConfig($provider_id, $config);

  /**
   * Update instance config.
   *
   * @param array $config
   *   New config data array for the instance.
   */
  public function setSettings(array $config);

  /**
   * Get provider list.
   *
   * @return string[]
   *   Provider id list.
   */
  public function getProviders();

  /**
   * Get config for a provider.
   *
   * @param string $provider_id
   *   Id of AccessProvider plugin.
   *
   * @return array|mixed
   *   Configuration for a provider plugin.
   */
  public function getProviderConfig($provider_id);

  /**
   * Get config for all providers.
   *
   * @return array
   *   Array containing provider config. Indexes are provider plugin id's.
   */
  public function getAllProviderConfig();

  /**
   * Retrieve config for the Access Consumer.
   *
   * @return array
   *   Config data for the instance.
   */
  public function getSettings();

}
