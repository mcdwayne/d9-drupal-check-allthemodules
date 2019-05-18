<?php

namespace Drupal\multi_site_configuration\Configuration;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Class MultiSiteConfigSubscriber.
 *
 * @package Drupal\multi_site_configuration
 */
class MultiSiteConfiguration implements ConfigFactoryOverrideInterface {

  /**
   * Returns config overrides.
   *
   * @param array $names
   *   A list of configuration names that are being loaded.
   *
   * @return array
   *   An array keyed by configuration name of override data. Override data
   *   contains a nested array structure of overrides.
   */
  public function loadOverrides($names) {
    $overrides = array();
    if (in_array('system.site', $names)) {
      $settings = array();
      $negotiator = \Drupal::service('domain.negotiator');
      $domain = $negotiator->getActiveDomain();
      if (isset($domain) && NULL !== $domain->id()) {
        $domain_key = $domain->id();
        $configFactory = \Drupal::config('multi_site_configuration.settings');
        $site_name = $configFactory->get($domain_key . '.domain_site_name');
        $site_slogan = $configFactory->get($domain_key . '.domain_site_slogan');
        $site_mail = $configFactory->get($domain_key . '.domain_site_mail');
        $site_403 = $configFactory->get($domain_key . '.domain_site_403');
        $site_404 = $configFactory->get($domain_key . '.domain_site_404');
        $site_front = $configFactory->get($domain_key . '.domain_site_front');

        // Create the new settings array to override the configuration.
        $settings = array_filter([
          'name' => $site_name,
          'slogan' => $site_slogan,
          'mail' => $site_mail,
          'page' => [
            '403' => $site_403,
            '404' => $site_404,
            'front' => $site_front]
        ]);
      }
      if (count($settings) > 0) {
        // Assign the new setting configuration.
        $overrides['system.site'] = $settings;
      }
    }
    return $overrides;
  }

  /**
   * The string to append to the configuration static cache name.
   *
   * @return string
   *   A string to append to the configuration static cache name.
   */
  public function getCacheSuffix() {
    return 'MultiSiteConfigurationOverrider';
  }

  /**
   * Gets the cacheability metadata associated with the config factory override.
   *
   * @param string $name
   *   The name of the configuration override to get metadata for.
   *
   * @return \Drupal\Core\Cache\CacheableMetadata
   *   A cacheable metadata object.
   */
  public function getCacheableMetadata($name) {
    return new CacheableMetadata();
  }

  /**
   * Creates a configuration object for use during install and synchronization.
   *
   * If the overrider stores its overrides in configuration collections then
   * it can have its own implementation of
   * \Drupal\Core\Config\StorableConfigBase. Configuration overriders can link
   * themselves to a configuration collection by listening to the
   * \Drupal\Core\Config\ConfigEvents::COLLECTION_INFO event and adding the
   * collections they are responsible for. Doing this will allow installation
   * and synchronization to use the overrider's implementation of
   * StorableConfigBase.
   *
   * @see \Drupal\Core\Config\ConfigCollectionInfo
   * @see \Drupal\Core\Config\ConfigImporter::importConfig()
   * @see \Drupal\Core\Config\ConfigInstaller::createConfiguration()
   *
   * @param string $name
   *   The configuration object name.
   * @param string $collection
   *   The configuration collection.
   *
   * @return \Drupal\Core\Config\StorableConfigBase
   *   The configuration object for the provided name and collection.
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

}
