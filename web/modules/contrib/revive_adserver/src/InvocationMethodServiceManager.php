<?php

namespace Drupal\revive_adserver;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Invocation Method plugin manager.
 */
class InvocationMethodServiceManager extends DefaultPluginManager {

  /**
   * Constructs a new InvocationMethodServiceManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/ReviveAdserver/InvocationMethod', $namespaces, $module_handler, 'Drupal\revive_adserver\InvocationMethodServiceInterface', 'Drupal\revive_adserver\Annotation\InvocationMethodService');

    $this->alterInfo('revive_adserver_invocation_method_service_info');
    $this->setCacheBackend($cache_backend, 'revive_adserver_invocation_method_service_plugins');
  }

  /**
   * Get an options list for all available invocation methods.
   *
   * @return array
   *   An array of options keyed by plugin ID with label values.
   */
  public function getInvocationMethodOptionList() {
    $options = [];
    foreach ($this->getDefinitions() as $definition) {
      $options[$definition['id']] = $definition['label'];
    }
    return $options;
  }

  /**
   * Load a invocation method from user input.
   *
   * @param string $input
   *   Input provided from a field.
   *
   * @return \Drupal\revive_adserver\InvocationMethodServiceInterface|bool
   *   The loaded plugin.
   */
  public function loadInvocationMethodFromInput($input) {
    $definition = $this->loadDefinitionFromInput($input);
    return $definition ? $this->createInstance($definition['id'], ['input' => $input]) : FALSE;
  }

  /**
   * Load a plugin definition from an input.
   *
   * @param string $input
   *   An input string.
   *
   * @return array|bool
   *   A plugin definition.
   */
  public function loadDefinitionFromInput($input) {
    $definitions = $this->getDefinitions();
    return isset($definitions[$input]) ? $definitions[$input] : FALSE;
  }

  /**
   * @inheritdoc
   */
  protected function findDefinitions() {
    $definitions = parent::findDefinitions();
    uasort($definitions, [
      'Drupal\Component\Utility\SortArray',
      'sortByWeightElement',
    ]);
    return $definitions;
  }

  /**
   * Returns the Revive Adserver zones from config.
   *
   * @return array
   *   Key/value zones.
   */
  public function getZonesOptionList() {
    $zone_config = $this->getZonesFromConfig();
    $zones = [];
    foreach ($zone_config as $zone) {
      $zones[$zone['id']] = $zone['name'];
    }

    return $zones;
  }

  /**
   * Returns the zones from config.
   *
   * @return array|mixed|null
   *   Zone configuration.
   */
  protected function getZonesFromConfig() {
    $config = \Drupal::config('revive_adserver.settings');
    return $config->get('zones');
  }

  /**
   * Returns a single zone from config.
   *
   * @return array|null
   *   Zone configuration or null, if zone not found.
   */
  public function getZoneFromConfig($zoneId) {
    $zones = $this->getZonesFromConfig();
    foreach ($zones as $zone) {
      if ($zone['id'] === $zoneId) {
        return $zone;
      }
    }
    return NULL;
  }

}
