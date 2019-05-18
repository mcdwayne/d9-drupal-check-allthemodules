<?php

namespace Drupal\authorization_code;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\FallbackPluginManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * The plugin manager class for all plugins of authorization_code.
 */
class PluginManager extends DefaultPluginManager implements FallbackPluginManagerInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, $plugin_type, $subdir, $plugin_interface, $annotation_class) {
    $this->alterInfo($plugin_type);
    $this->setCacheBackend($cache_backend, "authorization_code_$plugin_type");

    parent::__construct($subdir, $namespaces, $module_handler, $plugin_interface, $annotation_class);
  }

  /**
   * {@inheritdoc}
   */
  public function getFallbackPluginId($plugin_id, array $configuration = []) {
    return 'broken';
  }

  /**
   * Creates the plugin and returns a fallback if the plugin creation fails.
   *
   * This will not throw PluginException because PluginManager implements
   * FallbackPluginManagerInterface.
   *
   * @param string $plugin_id
   *   The plugin id.
   * @param array $configuration
   *   The plugin configuration.
   *
   * @see \Drupal\Component\Plugin\FallbackPluginManagerInterface
   * @see \Drupal\Component\Plugin\PluginManagerBase::createInstance()
   *
   * @return \Drupal\authorization_code\UserIdentifierInterface|\Drupal\authorization_code\CodeGeneratorInterface|\Drupal\authorization_code\CodeSenderInterface
   *   The plugin instance.
   */
  public function createPluginInstanceWithFallback($plugin_id, array $configuration = []) {
    try {
      return parent::createInstance($plugin_id, $configuration);
    }
    catch (PluginException $e) {
      throw new \LogicException('Unreachable point');
    }
  }

}
