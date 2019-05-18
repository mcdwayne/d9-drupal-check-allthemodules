<?php

namespace Drupal\api_tokens;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\CategorizingPluginManagerTrait;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides an ApiToken plugin manager.
 *
 * @see \Drupal\api_tokens\Annotation\ApiToken
 * @see \Drupal\api_tokens\ApiTokenPluginInterface
 * @see \Drupal\api_tokens\ApiTokenBase
 * @see plugin_api
 */
class ApiTokenManager extends DefaultPluginManager implements ApiTokenManagerInterface {
  use CategorizingPluginManagerTrait;

  /**
   * Constructs an ApiTokenManager object.
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
      'Plugin/ApiToken',
      $namespaces,
      $module_handler,
      'Drupal\api_tokens\ApiTokenPluginInterface',
      'Drupal\api_tokens\Annotation\ApiToken'
    );
    $this->alterInfo('api_tokens_info');
    $this->setCacheBackend($cache_backend, 'api_tokens_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);
    $this->processDefinitionCategory($definition);
  }

}
