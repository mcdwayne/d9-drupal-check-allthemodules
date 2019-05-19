<?php

/**
 * @file
 * Contains \Drupal\wisski_pipe\ProcessorManager.
 */

namespace Drupal\wisski_pipe;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Component\Plugin\FallbackPluginManagerInterface;

/**
 * Manages processors.
 */
class ProcessorManager extends DefaultPluginManager implements FallbackPluginManagerInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/wisski_pipe/Processor', $namespaces, $module_handler, 'Drupal\wisski_pipe\ProcessorInterface', 'Drupal\wisski_pipe\Annotation\Processor');

    $this->setCacheBackend($cache_backend, 'wisski_pipe_processor');
  }

  
  /**
   * {@inheritdoc}
   */
  public function getFallbackPluginId($plugin_id, array $configuration = array()) {
    \Drupal::logger('wisski_pipe')->warning("Someone wants to instantiate non-existing plugin {id}.", ['id' => $plugin_id]);
    return 'noop';
  }

}

