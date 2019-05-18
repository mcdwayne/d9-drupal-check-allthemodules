<?php

namespace Drupal\apitools;

use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Provides the Model plugin manager.
 */
class ModelManager extends DefaultPluginManager implements ModelManagerInterface, ContainerAwareInterface {

  use ContainerAwareTrait;

  /**
   * @var ClassResolverInterface
   */
  protected $classResolver;

  /**
   * @var ModelControllerInterface[]
   */
  protected $controllers = [];

  /**
   * Constructs a new ModelManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, ClassResolverInterface $class_resolver) {
    parent::__construct('Plugin/ApiTools', $namespaces, $module_handler, 'Drupal\apitools\ModelInterface', 'Drupal\apitools\Annotation\ApiToolsModel');

    $this->classResolver = $class_resolver;

    $this->alterInfo('apitools_model_info');
    $this->setCacheBackend($cache_backend, 'apitools_model_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitionByMethod($client_method) {
    foreach ($this->getDefinitions() as $definition) {
      if (!isset($definition['client_properties'])) {
        continue;
      }
      if (!isset($definition['client_properties'][$client_method]) && !in_array($client_method, $definition['client_properties'])) {
        continue;
      }
      return $definition;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getModel($model_name, array $values = []) {
    if ($definition = $this->getDefinition($model_name)) {
      $configuration = array_merge($definition, $values);
      return $this->createInstance($model_name, $configuration);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getModelController($model_name, $provider_name) {
    if (!isset($this->controllers[$provider_name])) {
      $this->controllers[$provider_name] = [];
    }
    if (!isset($this->controllers[$provider_name][$model_name])) {
      $this->controllers[$provider_name][$model_name] = NULL;
      if ($definition = $this->getDefinition($model_name)) {
        $class = !empty($definition['controller']) ? $definition['controller'] : "Drupal\apitools\ModelControllerDefault";
        $this->controllers[$provider_name][$model_name] = $this->createControllerInstance($class, $definition);
      }
    }
    return $this->controllers[$provider_name][$model_name];
  }

  /**
   * {@inheritdoc}
   */
  public function getModelControllerByMethod($client_method, $provider_name) {
    if ($definition = $this->getDefinitionByMethod($client_method)) {
      return $this->getModelController($definition['id'], $provider_name);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterDefinitions(&$definitions) {
    foreach ($definitions as $plugin_id => &$definition) {
      if (!empty($definition['client_property'])) {
        $definition['client_properties'] = [$definition['client_property']];
        unset($definition['client_property']);
      }
    }
    parent::alterDefinitions($definitions);
  }

  /**
   * Create a new ModelControllerInterface object.
   *
   * @param $class
   *   Class reference string.
   * @param array $definition
   *   Plugin definition array.
   *
   * @return ModelControllerInterface|bool
   */
  protected function createControllerInstance($class, array $definition = []) {
    if (is_subclass_of($class, 'Drupal\apitools\ModelControllerInterface')) {
      $controller = $class::createInstance($this->container, $definition);
    }
    else {
      $controller = new $class($definition);
    }
    return $controller;
  }
}
