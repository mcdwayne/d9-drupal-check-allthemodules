<?php

namespace Drupal\npm\Plugin;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Node Package Manager plugin manager.
 */
class NpmExecutablePluginManager extends DefaultPluginManager {

  /**
   * Constructs NpmPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/NpmExecutable',
      $namespaces,
      $module_handler,
      'Drupal\npm\Plugin\NpmExecutableInterface',
      'Drupal\npm\Annotation\NpmExecutable'
    );
    $this->alterInfo('npm_executable_info');
    $this->setCacheBackend($cache_backend, 'npm_executable_info');
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions() {
    $definitions = parent::getDefinitions();
    uasort($definitions, 'Drupal\Component\Utility\SortArray::sortByWeightElement');
    return $definitions;
  }

  /**
   * Returns the first available npm executable.
   *
   * @return \Drupal\npm\Plugin\NpmExecutableInterface
   * @throws \Drupal\npm\Plugin\NpmExecutableNotFoundException
   */
  public function getExecutable() {
    $definitions = $this->getDefinitions();
    foreach ($definitions as $definition) {
      /** @var \Drupal\npm\Plugin\NpmExecutableInterface $plugin */
      try {
        $plugin = $this->createInstance($definition['id']);
      } catch (PluginException $e) {
        // This should never happen. We're iteration over fresh definitions.
      }

      if ($plugin->isAvailable()) {
        return $plugin;
      }
    }

    throw new NpmExecutableNotFoundException('No npm executable found.');
  }

}

class NpmExecutableNotFoundException extends \Exception {}
