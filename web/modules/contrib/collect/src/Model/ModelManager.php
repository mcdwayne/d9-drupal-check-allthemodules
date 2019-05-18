<?php
/**
 * @file
 * Contains \Drupal\collect\ModelManager.
 */

namespace Drupal\collect\Model;

use Drupal\collect\CollectContainerInterface;
use Drupal\collect\Entity\Container;
use Drupal\collect\Entity\Model;
use Drupal\collect\Processor\ProcessorManagerInterface;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manager of model plugins and models.
 */
class ModelManager extends DefaultPluginManager implements ModelManagerInterface {

  /**
   * Plugin suggestions for URIs.
   *
   * @var string[]
   */
  protected $suggestions;

  /**
   * The injected logger.
   *
   * @var \Psr\Log\LoggerInterface;
   */
  protected $logger;

  /**
   * The injected container entity storage.
   *
   * @var \Drupal\collect\CollectStorageInterface
   */
  protected $containerStorage;

  /**
   * The injected Collect processor plugin manager.
   *
   * @var \Drupal\collect\Processor\ProcessorManagerInterface
   */
  protected $processorManager;

  /**
   * Constructs a new ModelManager.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, LoggerChannelFactoryInterface $logger_factory, EntityManagerInterface $entity_manager, ProcessorManagerInterface $processor_manager) {
    parent::__construct('Plugin/collect/Model', $namespaces, $module_handler, 'Drupal\collect\Model\ModelPluginInterface', 'Drupal\collect\Annotation\Model');
    $this->logger = $logger_factory->get('collect');
    // Tolerate that the entity storage is not available during install.
    $this->containerStorage = $entity_manager->hasDefinition('collect_container') ? $entity_manager->getStorage('collect_container') : NULL;
    $this->processorManager = $processor_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function createInstanceFromUri($uri) {
    if ($config = $this->loadModelByUri($uri)) {
      return $this->createInstanceFromConfig($config);
    }
    // No matching configuration. Return default model plugin.
    return $this->createInstance('default');
  }

  /**
   * {@inheritdoc}
   */
  public function createInstanceFromConfig(ModelInterface $model) {
    if (empty($model)) {
      // No model given, return default model plugin.
      return $this->createInstance('default');
    }

    /** @var \Drupal\collect\Model\ModelPluginInterface $instance */
    try {
      // @todo Pass $model directly, after https://www.drupal.org/node/2100549
      $instance = $this->createInstance($model->getPluginId(), ['config' => $model]);
    }
    catch (PluginNotFoundException $e) {
      $this->logger->warning('Model %model_id referenced missing plugin ID %plugin_id', ['%model_id' => $model->id(), '%plugin_id' => $model->getPluginId()]);
      $instance = $this->createInstance('default');
    }

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function loadModelByUri($uri) {
    $models = Model::loadMultiple();
    uasort($models, 'Drupal\collect\Entity\Model::sort');
    foreach ($models as $model) {
      if ($model->status() && $this->matchUri($model->getUriPattern(), $uri)) {
        return $model;
      }
    }
    // No matching config found.
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function suggestModel(CollectContainerInterface $container) {
    // Try matching the patterns declared by each plugin definition.
    foreach ($this->getDefinitions() as $definition) {
      if (isset($definition['patterns']) && (!isset($definition['hidden']) || !$definition['hidden'])) {
        foreach ($definition['patterns'] as $pattern) {
          if ($this->matchUri($pattern, $container->getSchemaUri())) {
            // Found a plugin that can suggest config.
            /** @var \Drupal\collect\Model\ModelPluginInterface $plugin */
            $plugin = $this->createInstance($definition['id']);
            if ($suggested_config = $definition['class']::suggestConfig($container, $plugin->getPluginDefinition())) {
              return $suggested_config;
            }
          }
        }
      }
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function suggestProperties(ModelInterface $model) {
    $property_definitions = &drupal_static(__FUNCTION__);
    if (!isset($property_definitions)) {
      $property_definitions = array();
    }

    if (!isset($property_definitions[$model->id()])) {
      $model_typed_data = $this->createInstanceFromConfig($model)->getTypedData();

      // Add static properties defined by model plugin.
      $property_definitions[$model->id()] = $model_typed_data->getStaticPropertyDefinitions();

      // Dynamic models need sample data to generate property definitions. If no
      // sample data is found, no dynamic property definitions can be generated,
      // but it is not an error.
      if ($model_typed_data instanceof DynamicModelTypedDataInterface) {
        $uri_pattern = $model->getUriPattern();
        if (!empty($uri_pattern) && $collect_container = $this->loadContainer($uri_pattern)) {
          $property_definitions[$model->id()] += $model_typed_data->generatePropertyDefinitions($collect_container);
        }
      }
    }

    return $property_definitions[$model->id()];
  }

  /**
   * Matches an URI against a pattern.
   *
   * If the URI begins with the pattern string, it is a match.
   *
   * @todo Implement wildcards.
   *
   * @param string $pattern
   *   Pattern to test against.
   * @param string $uri
   *   URI to test.
   *
   * @return bool
   *   TRUE if it is a match, FALSE otherwise.
   */
  protected function matchUri($pattern, $uri) {
    return strpos($uri, $pattern) === 0;
  }

  /**
   * {@inheritdoc}
   */
  public function loadContainer($uri_pattern) {
    $ids = $this->containerStorage->getIdsByUriPatterns([$uri_pattern]);
    return $ids ? Container::load(array_pop($ids)) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function isModelRevisionable(CollectContainerInterface $container) {
    $model = $this->loadModelByUri($container->getSchemaUri());
    if ($model) {
      return $model->get('container_revision');
    }
    return FALSE;
  }

}
