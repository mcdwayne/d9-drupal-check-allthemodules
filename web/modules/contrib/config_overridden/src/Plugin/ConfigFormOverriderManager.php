<?php

namespace Drupal\config_overridden\Plugin;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Defines the FormOverriderManager plugin manager.
 */
class ConfigFormOverriderManager extends DefaultPluginManager {

  /**
   * Constructs a ConfigFormOverriderManager object.
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
    parent::__construct('Plugin/ConfigFormOverrider', $namespaces, $module_handler, 'Drupal\config_overridden\Plugin\ConfigFormOverriderInterface', 'Drupal\config_overridden\Annotation\ConfigFormOverrider');
    $this->alterInfo('form_overrider_info');
    $this->setCacheBackend($cache_backend, 'form_overrider');
    // $this->configFactory = $config_factory;
    // $this->services = $config_factory->get('saipolfm_sms_service.services');.
  }

  /**
   * Find a plugin.
   *
   * @return ConfigFormOverriderInterface
   *   Return config form override interface.
   */
  public function findPlugin(&$form, FormStateInterface $form_state, $form_id) {
    $definitions = $this->getDefinitions();
    // Delete null plugin.
    $null_plugin = $definitions['form_null'];
    unset($definitions['form_null']);

    // Sort by weight property.
    uasort($definitions, 'Drupal\Component\Utility\SortArray::sortByWeightElement');

    $plugin_config = [
      'form' => &$form,
      'form_state' => $form_state,
      'form_id' => $form_id,
    ];

    // @var $plugin ConfigFormOverriderInterface
    foreach ($definitions as $definition) {
      $plugin = $this->createInstance($definition['id'], $plugin_config);
      if ($plugin->isApplicable()) {
        return $plugin;
      }

      unset($plugin);
    }

    // Create fallback instance in case if nothing found.
    return $this->createInstance($null_plugin['id'], $plugin_config);
  }

  public function createInstance($plugin_id, array $configuration = []) {
    /**
     * @var $instance \Drupal\config_overridden\Plugin\ConfigFormOverriderInterface
     */
    $instance = parent::createInstance($plugin_id, $configuration);

    $instance->setForm($configuration['form'], $configuration['form_state'], $configuration['form_id']);

    return $instance;
  }
}
