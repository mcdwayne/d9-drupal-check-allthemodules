<?php

namespace Drupal\entity_router;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\entity_router\Annotation\EntityResponseHandler;

/**
 * The manager of the "EntityResponseHandler" plugins.
 */
class EntityResponseHandlerManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(EntityResponseHandler::DIRECTORY, $namespaces, $module_handler, EntityResponseHandlerInterface::class, EntityResponseHandler::class);

    $this->setCacheBackend($cache_backend, 'entity_response_handler');
    $this->alterInfo('entity_response_handler_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = []): EntityResponseHandlerInterface {
    $definition = $this->getDefinition($plugin_id);

    if (!empty($definition['dependencies'])) {
      $missing_dependencies = array_diff($definition['dependencies'], array_keys($this->moduleHandler->getModuleList()));

      if (!empty($missing_dependencies)) {
        throw new PluginException(sprintf('The following dependencies are missing and disallow instantiating the "%s" plugin: %s', $plugin_id, implode(', ', $missing_dependencies)));
      }
    }

    return parent::createInstance($plugin_id, $configuration);
  }

}
