<?php

namespace Drupal\vde_drush;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\vde_drush\Annotation\FormatManipulator;

/**
 * Provides format manipulator plugin manager.
 */
class FormatManipulatorLoader extends DefaultPluginManager {

  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/FormatManipulator',
      $namespaces,
      $module_handler,
      FormatManipulatorInterface::class,
      FormatManipulator::class, []
    );

    $this->alterInfo('format_manipulator_info');
    $this->setCacheBackend($cache_backend, 'format_manipulator_plugins');
  }

  /**
   * Creates a format manipulator plugin instance.
   *
   * @param string $plugin_id
   * @param array $configuration
   *
   * @return \Drupal\vde_drush\FormatManipulatorInterface
   */
  public function createInstance($plugin_id, array $configuration = []) {
    try {
      $plugin_definition = $this->getDefinition($plugin_id);
    }
    catch (PluginNotFoundException $e) {
      // Notify users about the absence of requested file format manipulator plugin.
      throw new \Exception(dt('No format handler has been found for the format \'%plugin_id\'.', [
        '%plugin_id' => $plugin_id
      ]));
    }

    // Apply the custom configuration.
    array_merge($plugin_definition, $configuration);

    // Resolve plugin class.
    $plugin_class = DefaultFactory::getPluginClass($plugin_id, $plugin_definition);

    return new $plugin_class();
  }

}
