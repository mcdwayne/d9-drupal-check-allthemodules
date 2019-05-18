<?php

namespace Drupal\ipsum\Plugin\Type;

use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages discovery and instantiation of ipsum provider plugins.
 */
class IpsumPluginManager extends DefaultPluginManager {

  /**
   * Constructs a new \Drupal\ipsum\Plugin\Type\IpsumPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   Cache backend instance to use.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache, LanguageManagerInterface $language_manager, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/Ipsum/provider',
      $namespaces,
      $module_handler,
      'Drupal\ipsum\Plugin\ProviderInterface',
      'Drupal\ipsum\Annotation\IpsumProvider'
    );

    $this->setCacheBackend($cache, 'ipsum_plugins', ['ipsum_plugins']);
    $this->factory = new DefaultFactory($this->getDiscovery());
    $this->discovery = new AnnotatedClassDiscovery($this->subdir, $namespaces, $this->pluginDefinitionAnnotationName);
    $this->alterInfo('ipsum_provider');
  }

}
