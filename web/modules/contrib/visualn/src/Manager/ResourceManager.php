<?php

namespace Drupal\visualn\Manager;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

use Drupal\visualn\Resource;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\visualn\Plugin\DataType\Deriver\ResourceDataDefinition;

//use Drupal\Core\TypedData\TypedDataManager;
//use Drupal\Core\TypedData\TypedDataManagerInterface;

/**
 * Provides the VisualN Resource plugin manager.
 */
class ResourceManager extends DefaultPluginManager {
//class ResourceManager extends TypedDataManager {


  /**
   * Constructs a new ResourceManager object.
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
    parent::__construct('Plugin/VisualN/Resource', $namespaces, $module_handler, 'Drupal\visualn\Core\VisualNResourceInterface', 'Drupal\visualn\Annotation\VisualNResource');

    $this->alterInfo('visualn_resource_info');
    $this->setCacheBackend($cache_backend, 'visualn_resource_plugins');
  }

  // @todo: implement createInstance
  //    see FieldTypePluginManager::createInstance() and TypedDataManager::createInstance() and TypedDataManager::create()

  /**
   * {@inheritdoc}
   */
  public function createInstance($resource_type, array $configuration = []) {


    // @todo: instantiate in _construct()
    $typedDataManager = \Drupal::service('typed_data_manager');

    $definition = ResourceDataDefinition::create("visualn_resource:$resource_type");
    $definition->setInitialParams($configuration['raw_input']);

    // @todo: see https://www.drupal.org/project/drupal/issues/2874458
    // @todo: if definition is created for othe type i.e. MapDataDefinition::create(), the returned class will be Map but not plugin one, that is weird, explore

    // @todo: check 'name' and 'parent' keys
    $configuration = [
      'data_definition' => $definition,
      'name' => NULL,
      'parent' => NULL,
    ];

    // @todo: undefined index $configuration['parent']
    return $typedDataManager->createInstance("visualn_resource:$resource_type", $configuration);
    //return $typedDataManager->createInstance("visualn_resource:$resource_type", $definition);
    //return $this->typedDataManager->createInstance("visualn_resource:$field_type", $configuration);
    //return $this->typedDataManager->createInstance("field_item:$field_type", $configuration);


/*
    return parent::createInstance($resource_type, $configuration);

    // @todo: values are passed as array and then transformed into data definition in a similar method as getItemDefinition()

    //$configuration['data_definition'] = $configuration['resource_definition'];
    $raw_input = $configuration['resource_definition'];
    $output_type = $resource_type;
*/

/*
    $configuration['data_definition'] = $configuration['field_definition']->getItemDefinition();
    return $this->typedDataManager->createInstance("field_item:$field_type", $configuration);
*/
  }

  public function getPluginClass($type) {
    $plugin_definition = $this->getDefinition($type, FALSE);
    return $plugin_definition['class'];
  }

}
