<?php
/**
 * @file
 * Contains \Drupal\collect\TypedData\TypedDataProvider.
 */

namespace Drupal\collect\TypedData;

use Drupal\collect\CollectContainerInterface;
use Drupal\collect\Model\ModelManagerInterface;
use Drupal\collect\Model\ModelPluginInterface;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\Core\TypedData\TypedDataManager;

/**
 * Provides the data of a container as Typed Data.
 */
class TypedDataProvider {

  /**
   * The injected Collect model plugin & config manager.
   *
   * @var \Drupal\collect\Model\ModelManagerInterface
   */
  protected $modelManager;

  /**
   * The injected Typed Data manager.
   *
   * @var \Drupal\Core\TypedData\TypedDataManager
   */
  protected $typedDataManager;

  /**
   * The injected container storage.
   *
   * @var \Drupal\collect\CollectStorageInterface
   */
  protected $containerStorage;

  /**
   * Constructs a new TypedDataProvider object.
   *
   * @param \Drupal\collect\Model\ModelManagerInterface $model_manager
   *   A Collect model manager instance.
   * @param \Drupal\Core\TypedData\TypedDataManager $typed_data_manager
   *   A typed data manager instance.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   */
  public function __construct(ModelManagerInterface $model_manager, TypedDataManager $typed_data_manager, EntityManagerInterface $entity_manager) {
    $this->modelManager = $model_manager;
    $this->typedDataManager = $typed_data_manager;
    $this->containerStorage = $entity_manager->getStorage('collect_container');
  }

  /**
   * Returns a Typed Data object for the container data.
   *
   * The object is instantiated according to the model matched for the container
   * data.
   *
   * @param \Drupal\collect\CollectContainerInterface $collect_container
   *   The collect container item.
   *
   * @return \Drupal\collect\TypedData\CollectDataInterface
   *   The data as Typed Data.
   */
  public function getTypedData(CollectContainerInterface $collect_container) {
    $model_plugin = $this->modelManager->createInstanceFromUri($collect_container->getSchemaUri());
    $parsed_data = $model_plugin->parse($collect_container);
    $data_definition = $this->createDataDefinition($model_plugin);
    $data = $this->typedDataManager->create($data_definition, ['data' => $parsed_data, 'container' => $collect_container], t('Data'));
    return $data;
  }

  /**
   * Resolves a subaddress string to a value.
   *
   * @todo Resolve complex data path.
   * @todo Follow relations.
   * @todo Follow entity references.
   *
   * @param \Drupal\Core\TypedData\ComplexDataInterface $data
   *   A complex data object.
   * @param string $subaddress
   *   The subaddress to resolve. It can be the name of a model property.
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface
   *   The data identified by the subaddress.
   *
   * @throws \InvalidArgumentException
   *   If the subaddress is not the name of an existing model property.
   */
  public function resolveSubaddress(ComplexDataInterface $data, $subaddress) {
    // Return the model property identified by the subaddress. Throws exception
    // if property is not defined.
    return $data->get($subaddress);
  }

  /**
   * Returns a Typed Data object for the given URI.
   *
   * @param string $uri
   *   The URI to find data for. Should be the Origin URI of a container, with
   *   optional subaddress as fragment (separated from the Origin URI with "#").
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface
   *   The data as Typed Data.
   *
   * @throws \InvalidArgumentException
   *   If the URI does not resolve to any data.
   */
  public function resolveDataUri($uri) {
    // Remove fragment from URI.
    $uri_split = explode('#', $uri);
    $uri_no_fragment = $uri_split[0];

    // Load container.
    $container = $this->containerStorage
      ->loadByOriginUri($uri_no_fragment);
    if (empty($container)) {
      throw new \InvalidArgumentException(SafeMarkup::format('No container found with given Origin URI "@uri"', ['@uri' => $uri_no_fragment]));
    }
    $container_typed_data = $this->getTypedData($container);

    // Resolve fragment if it exists.
    if (isset($uri_split[1])) {
      $fragment = $uri_split[1];
      // Resolving may throw InvalidArgumentException.
      return $this->resolveSubaddress($container_typed_data, $fragment);
    }

    // If there is no fragment, return full container data.
    return $container_typed_data;
  }

  /**
   * Creates a data definition for a given model plugin.
   *
   * @param \Drupal\collect\Model\ModelPluginInterface $model_plugin
   *   The Collect model plugin.
   *
   * @return \Drupal\collect\TypedData\CollectDataDefinition
   *   The data definition object.
   */
  public function createDataDefinition(ModelPluginInterface $model_plugin) {
    return CollectDataDefinition::create('collect')
      ->setLabel($model_plugin->getLabel())
      ->setModelTypedData($model_plugin->getTypedData())
      ->setQueryEvaluator($model_plugin->getQueryEvaluator());
  }
}
