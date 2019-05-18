<?php
/**
 * @file
 * Contains \Drupal\collect\Plugin\search_api\datasource\CollectDatasource.
 */

namespace Drupal\collect\Plugin\search_api\datasource;

use Drupal\collect\CollectStorageInterface;
use Drupal\collect\Entity\Container;
use Drupal\collect\Model\ModelInterface;
use Drupal\collect\Model\ModelManagerInterface;
use Drupal\collect\TypedData\TypedDataProvider;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\search_api\Datasource\DatasourcePluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides Containers as sources, based on their model plugin.
 *
 * As derived from CollectDatasourceDeriver, the definition additionally
 * contains at least they keys "model" (the model ID) and "label".
 *
 * @see Drupal\collect\Plugin\search_api\datasource\CollectDatasourceDeriver
 *
 * @SearchApiDatasource(
 *   id = "collect",
 *   deriver = "Drupal\collect\Plugin\search_api\datasource\CollectDatasourceDeriver"
 * )
 */
class CollectDatasource extends DatasourcePluginBase {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|null
   */
  protected $configFactory;

  /**
   * The injected model plugin manager.
   *
   * @var \Drupal\collect\Model\ModelManagerInterface
   */
  protected $modelManager;

  /**
   * The injected container entity storage.
   *
   * @var \Drupal\collect\CollectStorageInterface
   */
  protected $containerStorage;

  /**
   * The injected container typed data provider.
   *
   * @var \Drupal\collect\TypedData\TypedDataProvider
   */
  protected $typedDataProvider;

  /**
   * Schema plugin instance.
   *
   * @var \Drupal\collect\Model\ModelPluginInterface
   */
  protected $schema;

  /**
   * Constructs a CollectDatasource datasource plugin.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, ModelManagerInterface $model_manager, CollectStorageInterface $container_storage, TypedDataProvider $typed_data_provider, ModelInterface $model, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->modelManager = $model_manager;
    $this->containerStorage = $container_storage;
    $this->typedDataProvider = $typed_data_provider;
    $this->schema = $this->modelManager->createInstanceFromConfig($model);
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.collect.model'),
      $container->get('entity.manager')->getStorage('collect_container'),
      $container->get('collect.typed_data_provider'),
      $container->get('entity.manager')->getStorage('collect_model')->load($plugin_definition['model']),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeId() {
    return 'collect_container';
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    return $this->typedDataProvider->createDataDefinition($this->schema)
      ->getPropertyDefinitions();
  }

  /**
   * Retrieves the config factory.
   *
   * @return \Drupal\Core\Config\ConfigFactoryInterface
   *   The config factory.
   */
  public function getConfigFactory() {
    return $this->configFactory ?: \Drupal::configFactory();
  }

  /**
   * Retrieves the config value for a certain key in the Search API settings.
   *
   * @param string $key
   *   The key whose value should be retrieved.
   *
   * @return mixed
   *   The config value for the given key.
   */
  protected function getConfigValue($key) {
    return $this->getConfigFactory()->get('search_api.settings')->get($key);
  }

  /**
   * Sets the config factory.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The new config factory.
   *
   * @return $this
   */
  public function setConfigFactory(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getItemIds($page = NULL) {
    $uri_pattern = $this->schema->getConfig()->getUriPattern();
    if (isset($page)) {
      $page_size = $this->getConfigValue('tracking_page_size');
      return $this->containerStorage->getIdsByUriPatterns([$uri_pattern], $page_size, $page * $page_size) ?: NULL;
    }
    return $this->containerStorage->getIdsByUriPatterns([$uri_pattern]) ?: NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultiple(array $ids) {
    // Load container entities and parse them into typed data.
    return array_map([$this->typedDataProvider, 'getTypedData'], Container::loadMultiple($ids));
  }

  /**
   * {@inheritdoc}
   */
  public function load($id) {
    $items = $this->loadMultiple([$id]);
    return $items ? reset($items) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getItemId(ComplexDataInterface $item) {
    /** @var \Drupal\collect\TypedData\CollectDataInterface $item */
    return $item->getContainer()->id();
  }

  /**
   * {@inheritdoc}
   */
  public function getItemLabel(ComplexDataInterface $item) {
    /** @var \Drupal\collect\TypedData\CollectDataInterface $item */
    return $item->getContainer()->label();
  }

  /**
   * {@inheritdoc}
   */
  public function getItemBundle(ComplexDataInterface $item) {
    return $this->getPluginDefinition()['id'];
  }

  /**
   * {@inheritdoc}
   */
  public function getItemUrl(ComplexDataInterface $item) {
    /** @var \Drupal\collect\TypedData\CollectDataInterface $item */
    return $item->getContainer()->urlInfo();
  }

  /**
   * {@inheritdoc}
   */
  public function viewItem(ComplexDataInterface $item, $view_mode, $langcode = NULL) {
    return $this->schema->buildTeaser($item);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $this->dependencies += parent::calculateDependencies();
    $this->addDependency('module', $this->getPluginDefinition()['provider']);
    return $this->dependencies;
  }

}
