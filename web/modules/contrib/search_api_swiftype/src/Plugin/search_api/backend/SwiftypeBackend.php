<?php

namespace Drupal\search_api_swiftype\Plugin\search_api\backend;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\Config;
use Drupal\Core\Entity\TypedData\EntityDataDefinitionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\TypedData\ComplexDataDefinitionInterface;
use Drupal\Core\Url;
use Drupal\search_api\Backend\BackendPluginBase;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Item\FieldInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Plugin\PluginFormTrait;
use Drupal\search_api\Plugin\search_api\data_type\value\TextValue;
use Drupal\search_api\Query\ConditionGroupInterface;
use Drupal\search_api\Query\ConditionInterface;
use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api\SearchApiException;
use Drupal\search_api\Utility\FieldsHelperInterface;
use Drupal\search_api\Utility\Utility;
use Drupal\search_api_swiftype\Exception\DocumentTypeNotFoundException;
use Drupal\search_api_swiftype\Exception\EngineNotFoundException;
use Drupal\search_api_swiftype\Exception\SwiftypeException;
use Drupal\search_api_swiftype\SwiftypeBackendInterface;
use Drupal\search_api_swiftype\SwiftypeClient\SwiftypeClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Swiftype backend for search api.
 *
 * @SearchApiBackend(
 *   id = "search_api_swiftype",
 *   label = @Translation("Swiftype"),
 *   description = @Translation("Use Swiftype as a Search API backend.")
 * )
 */
class SwiftypeBackend extends BackendPluginBase implements SwiftypeBackendInterface, PluginFormInterface {

  use PluginFormTrait;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * A config object for 'search_api_solr.settings'.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $searchApiSwiftypeSettings;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The Search API fields helper.
   *
   * @var \Drupal\search_api\Utility\FieldsHelperInterface
   */
  protected $fieldsHelper;

  /**
   * The Swiftype client service.
   *
   * Do *not* use directly; use getClientService() instead. Otherwise the
   * API key may not be set.
   *
   * @var \Drupal\search_api_swiftype\SwiftypeClient\SwiftypeClientInterface
   */
  private $clientService = NULL;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, ModuleHandlerInterface $module_handler, Config $search_api_swiftype_settings, LanguageManagerInterface $language_manager, FieldsHelperInterface $fields_helper, SwiftypeClientInterface $client_service, CacheBackendInterface $cache, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->moduleHandler = $module_handler;
    $this->searchApiSwiftypeSettings = $search_api_swiftype_settings;
    $this->languageManager = $language_manager;
    $this->fieldsHelper = $fields_helper;
    $this->clientService = $client_service;
    $this->cache = $cache;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler'),
      $container->get('config.factory')->get('search_api_swiftype.settings'),
      $container->get('language_manager'),
      $container->get('search_api.fields_helper'),
      $container->get('search_api_swiftype.client'),
      $container->get('cache.data'),
      $container->get('logger.channel.search_api_swiftype')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'api_key' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $account_link = Link::fromTextAndUrl($this->t('Swiftype account settings'), Url::fromUri('https://app.swiftype.com/settings/account', ['external' => TRUE]));
    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API key'),
      '#description' => $this->t('Your Swiftype API key to authenticate all requests. You can find the API key in your @swiftype_account.', ['@swiftype_account' => $account_link->toString()]),
      '#default_value' => $this->configuration['api_key'],
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function postInsert() {
    $factory = $this->getClientService()->getEntityFactory();
    $server_id = $this->getServer()->id();

    try {
      // Lookup engine on the server.
      $engine = $factory->createEngine($this->getClientService())->findByName($server_id);
      drupal_set_message($this->t('Successfully connected to engine @engine.', ['@engine' => Link::fromTextAndUrl($engine->getName(), $engine->getUrl(['external' => TRUE]))->toString()]));
    }
    catch (EngineNotFoundException $exc) {
      try {
        // No engine found with this name, so create a new engine.
        $engine = $this->getClientService()->createEngine($server_id);
        drupal_set_message($this->t('Created new engine @engine.', ['@engine' => Link::fromTextAndUrl($engine->getName(), $engine->getUrl(['external' => TRUE]))->toString()]));
      }
      catch (SwiftypeException $exc) {
        drupal_set_message($this->t('Failed creating an engine with name %engine_name.', ['%engine_name' => $server_id]), 'error');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postUpdate() {
    $config_original = $this->getServer()->original->getBackendConfig();
    // Swiftype account has changed, check if engine exists.
    try {
      $factory = $this->getClientService()->getEntityFactory();
      /** @var \Drupal\search_api_swiftype\SwiftypeEngine\SwiftypeEngineInterface $engine */
      $engine = $factory->createEngine($this->getClientService());
      $engine->findByName($this->getServer()->id());
      // Reindex all content if the API key has changed.
      $reindex = ($this->configuration['api_key'] !== $config_original['api_key']);
    }
    catch (EngineNotFoundException $exc) {
      // Create the engine.
      $this->getClientService()->createEngine($this->getServer()->id());
      $reindex = TRUE;
    }

    return $reindex;
  }

  /**
   * {@inheritdoc}
   */
  public function preDelete() {
    $factory = $this->getClientService()->getEntityFactory();
    $server_id = $this->getServer()->id();
    /** @var \Drupal\search_api_swiftype\SwiftypeEngine\SwiftypeEngineInterface $engine */
    $engine = $factory->createEngine($this->getClientService());

    try {
      // Lookup engine on the server.
      $engine->findByName($server_id);
      drupal_set_message($this->t('To remove the engine @engine from Swiftype, you have to login into your account and remove it manually.', ['@engine' => $server_id]));
    }
    catch (\Exception $exc) {
      $this->logger->warning('Engine @engine not found: "@message"', ['@engine' => $server_id, '@message' => $exc->getMessage()]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getClientService() {
    $this->clientService->setApiKey($this->configuration['api_key']);
    return $this->clientService;
  }

  /**
   * {@inheritdoc}
   */
  public function isAvailable() {
    return $this->getClientService()->isAuthorized();
  }

  /**
   * {@inheritdoc}
   */
  public function getEngineInfo($refresh = FALSE) {
    if ($refresh) {
      // Force reloading the engine data.
      Cache::invalidateTags(['search_api_swiftype:engines']);
    }

    $factory = $this->getClientService()->getEntityFactory();
    $engines = [];
    $server_id = $this->getServer()->id();
    /** @var \Drupal\search_api_swiftype\SwiftypeEngine\SwiftypeEngineInterface $engine */
    $engine = $factory->createEngine($this->getClientService());
    try {
      $engines[$server_id] = $engine->findByName($server_id)->getRawData();
    }
    catch (SwiftypeException $exc) {
      $this->logger->warning('Failed to load engine @engine: "@message"', ['@engine' => $server_id, '@message' => $exc->getMessage()]);
      return $engine;
    }
    if (!empty($engines[$server_id])) {
      return $factory->createEngine($this->getClientService(), $engines[$server_id]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedFeatures() {
    return [
      'search_api_facets',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function viewSettings() {
    $info = [];

    if ($this->getClientService()->isAuthorized()) {
      /** @var \Drupal\search_api_swiftype\SwiftypeEngine\SwiftypeEngineInterface $engine */
      $engine = $this->getEngineInfo(TRUE);
      $info[] = [
        'label' => $this->t('Overview'),
        'info' => Link::fromTextAndUrl($engine->getName(), $engine->getUrl())->toString(),
        'status' => 'ok',
      ];
      $info[] = [
        'label' => $this->t('Engine slug'),
        'info' => $engine->getSlug(),
      ];
      $info[] = [
        'label' => $this->t('Engine key'),
        'info' => $engine->getKey(),
      ];
      $info[] = [
        'label' => $this->t('Document count'),
        'info' => empty($engine->getDocumentCount()) ? $this->t('No documents indexed') : $engine->getDocumentCount(),
      ];
      $info[] = [
        'label' => $this->t('Last updated'),
        'info' => $engine->getUpdateDate()->format('Y-m-d H:i:s'),
      ];
    }
    else {
      $info[] = [
        'info' => $this->t('Authentification failed. Please review you API key.'),
        'status' => 'error',
      ];
    }

    return $info;
  }

  /**
   * {@inheritdoc}
   */
  public function addIndex(IndexInterface $index) {
    $factory = $this->getClientService()->getEntityFactory();
    $engine = $this->getEngineInfo();
    $document_type = $factory->createDocumentType($this->getClientService(), [
      'engine_id' => $engine->getId(),
    ]);

    Cache::invalidateTags(["search_api_swiftype:engine:{$engine->getSlug()}:document_types"]);

    foreach ($index->getDatasources() as $datasource) {
      try {
        $document_type->findByName($datasource->getPluginId());
      }
      catch (DocumentTypeNotFoundException $exc) {
        try {
          // Create document type if it does not exist.
          $this->getClientService()->createDocumentType($engine, $datasource->getPluginId());
        }
        catch (SwiftypeException $exc) {
          drupal_set_message($this->t('Failed creating document type @name.', ['@name' => $datasource->getPluginId()]), 'error');
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function updateIndex(IndexInterface $index) {
    if ($index->isSyncing()) {
      return;
    }
    $factory = $this->getClientService()->getEntityFactory();
    $engine = $this->getEngineInfo();
    $document_type = $factory->createDocumentType($this->getClientService(), [
      'engine_id' => $engine->getId(),
    ]);

    // Force getting a fresh list of document types from server.
    Cache::invalidateTags([
      "search_api_swiftype:engine:{$engine->getSlug()}:document_types",
      "search_api_swiftype:index:{$index->id()}",
    ]);

    // Recreate document types if necessary.
    foreach ($index->getDatasources() as $datasource) {
      try {
        $document_type = $document_type->findByName($datasource->getPluginId());
        if ($this->indexFieldsUpdated($index)) {
          // Delete document type because the field type could change.
          $document_type->delete();
          // Wait some seconds so the server doesn't complain :/.
          // @see https://community.swiftype.com/t/deleting-and-immediately-recreating-a-document-type-leads-to-error/631
          sleep(5);
          // Recreate the document type.
          $this->getClientService()->createDocumentType($engine, $datasource->getPluginId());
        }
      }
      catch (DocumentTypeNotFoundException $exc) {
        try {
          // Create document type.
          $this->getClientService()->createDocumentType($engine, $datasource->getPluginId());
        }
        catch (SwiftypeException $exc) {
          drupal_set_message($this->t('Failed re-creating document type @name.', ['@name' => $datasource->getPluginId()]), 'error');
        }
      }
    }

    if ($this->indexFieldsUpdated($index)) {
      $index->reindex();
    }
  }

  /**
   * Checks if the recently updated index had any fields changed.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The index that was just updated.
   *
   * @return bool
   *   TRUE if any of the fields were updated, FALSE otherwise.
   */
  protected function indexFieldsUpdated(IndexInterface $index) {
    // Get the original index, before the update. If it cannot be found, err on
    // the side of caution.
    if (!isset($index->original)) {
      return TRUE;
    }
    /** @var \Drupal\search_api\IndexInterface $original */
    $original = $index->original;

    $fields_old = $original->getFields();
    $fields_new = $index->getFields();
    if (!$fields_old && !$fields_new) {
      return FALSE;
    }

    foreach ($fields_new as $key => $field) {
      if (isset($fields_old[$key]) && ($field->getType() !== $fields_old[$key]->getType())) {
        // Field type has changed.
        return TRUE;
      }
    }

    if (array_diff_key($fields_old, $fields_new) || array_diff_key($fields_new, $fields_old)) {
      // Fields are added or removed.
      return TRUE;
    }

    // Nothing changed.
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function removeIndex($index) {
    $factory = $this->getClientService()->getEntityFactory();
    $engine = $this->getEngineInfo();
    $document_type = $factory->createDocumentType($this->getClientService(), [
      'engine_id' => $engine->getId(),
    ]);
    $cache_tags = [
      'search_api_swiftype:document_types',
      "search_api_swiftype:engine:{$engine->getSlug()}:document_types",
    ];
    Cache::invalidateTags($cache_tags);

    // Remove document types from engine.
    foreach ($index->getDatasources() as $datasource) {
      try {
        // Delete document type.
        $document_type->findByName($datasource->getPluginId())->delete();
      }
      catch (DocumentTypeNotFoundException $exc) {
        // Fail silently.
        $this->logger->warning('Failed loading document type @type: "@message"', ['@type' => $datasource->getPluginId(), '@message' => $exc->getMessage()]);
      }
      catch (Exception $exc) {
        drupal_set_message($this->t('Failed deleting document type @name.', ['@name' => $datasource->getPluginId()]), 'error');
        $this->logger->error('Failed deleting document type @type: "@message"', ['@type' => $datasource->getPluginId(), '@message' => $exc->getMessage()]);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function indexItems(IndexInterface $index, array $items) {
    $documents = $this->createDocumentsFromItems($index, $items);
    if (empty($documents)) {
      return [];
    }

    $engine = $this->getEngineInfo();
    $documents_to_index = [];
    $indexed_items = [];

    // Get all datasource ids from the resultset.
    $datasource_ids = array_unique(array_column($documents, 'datasourceId'));
    // Get all document types.
    $document_types_all = $this->getClientService()->listDocumentTypes($engine);
    $document_types = [];
    foreach ($datasource_ids as $datasource_id) {
      $keys = array_keys(array_column($document_types_all, 'name', 'slug'), $datasource_id, TRUE);
      $document_types[$datasource_id] = reset($keys);
    }

    // Group documents by document type.
    /** @var \Drupal\search_api\Item\ItemInterface[] $items */
    foreach ($items as $item_id => $item) {
      if (empty($documents[$item_id])) {
        // Well, seems somehow data got lost or somebody doesn't want this
        // particular item to be indexed.
        continue;
      }
      // This is somehow hacky but we need the Swiftype DocumentType here.
      // Maybe its better to store it in the Document object?
      $document_type = $document_types[$item->getDatasourceId()];
      if (empty($documents_to_index[$document_type])) {
        $documents_to_index[$document_type] = [];
      }
      $documents_to_index[$document_type][] = $documents[$item_id];
    }

    try {
      foreach ($documents_to_index as $type => $items_to_index) {
        $item_keys = array_column($items_to_index, 'externalId');
        $response = $this->getClientService()->bulkCreateOrUpdateDocuments($engine, $document_types_all[$type], $items_to_index);
        $success = array_filter($response, function ($item) {
          return TRUE === $item;
        });
        $indexed_items += array_intersect_key($item_keys, $success);
      }
    }
    catch (Exception $exc) {
      $this->logger->warning('Failed creating/updating documents: "@message". Response: @response', [
        '@message' => $exc->getMessage(),
        '@response' => implode("\n", $response),
      ]);
    }

    return $indexed_items;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteItems(IndexInterface $index, array $ids) {
    $engine = $this->getEngineInfo();

    $items = [];
    foreach ($ids as $id) {
      list($datasource_id) = Utility::splitCombinedId($id);
      $items[$id] = [
        'item_id' => $id,
        'datasource' => $datasource_id,
      ];
    }

    // Allow other modules to alter the list of deleted items.
    $this->moduleHandler->alter('search_api_swiftype_items_deleted', $items, $index);

    // Get all datasource ids from the resultset.
    $datasource_ids = array_unique(array_column($items, 'datasource'));
    // Get all document types.
    $document_types_all = $this->getClientService()->listDocumentTypes($engine);
    $document_types = [];
    foreach ($datasource_ids as $datasource) {
      $keys = array_keys(array_column($document_types_all, 'name', 'slug'), $datasource, TRUE);
      $document_types[$datasource] = reset($keys);
    }
    // Delete items grouped by document type.
    foreach ($document_types as $key => $document_type) {
      // Find items with current datasource.
      $items_grouped = array_keys(array_column($items, 'datasource', 'item_id'), $key, TRUE);
      // Delete items.
      $this->getClientService()->bulkDeleteDocuments($engine, $document_types_all[$document_type], $items_grouped);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAllIndexItems(IndexInterface $index, $datasource_id = NULL) {
    // Remove and recreate the index.
    $this->removeIndex($index);
    // This is indeed a very ugly hack but the API does not allow to create
    // document types right after removing them.
    // @see https://community.swiftype.com/t/deleting-and-immediately-recreating-a-document-type-leads-to-error/631
    sleep(5);
    $this->addIndex($index);
  }

  /**
   * {@inheritdoc}
   */
  public function search(QueryInterface $query) {
    $engine = $this->getEngineInfo();
    $index = $query->getIndex();
    $index_fields = $index->getFields();
    $index_fields += $this->getSpecialFields($index);

    // Get list of fields in index.
    $field_names = $this->getFieldNames($index);
    $document_types_all = $this->getClientService()->listDocumentTypes($engine);
    $type_fields = [];
    foreach ($index->getDatasourceIds() as $datasource_id) {
      $keys = array_keys(array_column($document_types_all, 'name', 'slug'), $datasource_id, TRUE);
      $slug = reset($keys);
      /** @var \Drupal\search_api_swiftype\SwiftypeDocumentType\SwiftypeDocumentTypeInterface $document_type */
      $document_type = $document_types_all[$slug];
      $type_fields[$slug] = $document_type->getFieldMapping();
    }

    // Extract keys.
    $keys = $query->getKeys();
    $fulltext_fields = $this->getQueryFulltextFields($query);

    $options = $query->getOptions();

    $limit = isset($options['limit']) ? $options['limit'] : 1000000;
    $page = 0;
    if (!empty($options['offset'])) {
      $page = ceil($options['offset'] / $limit) + 1;
    }

    $data = [
      'spelling' => 'always',
      'per_page' => $limit,
    ];
    if (!empty($page)) {
      $data['page'] = $page;
    }

    $sorts = $this->buildSorts($query);
    if (!empty($sorts)) {
      $data += $sorts;
    }

    if (is_array($keys)) {
      $keys = $this->flattenKeys($keys);
    }
    if (!empty($keys)) {
      $data['q'] = $keys;
    }

    $search_fields = [];
    foreach ($fulltext_fields as $search_field) {
      if (!isset($field_names[$search_field])) {
        continue;
      }
      /** @var \Drupal\search_api\Item\FieldInterface $field */
      $field = $index_fields[$search_field];
      $boost = ($field->getBoost() ? '^' . $field->getBoost() : '');
      foreach ($type_fields as $slug => $fields) {
        if (!isset($fields[$field_names[$search_field]])) {
          continue;
        }
        // Add document type specific field.
        $search_fields[$slug][] = $field_names[$search_field] . $boost;
      }
    }
    if (!empty($search_fields)) {
      $data['search_fields'] = $search_fields;
    }

    // Build filters.
    $condition_group = $query->getConditionGroup();
    if (($languages = $query->getLanguages()) !== NULL) {
      $condition_group->addCondition('search_api_language', $languages, 'IN');
    }
    $filters = $this->buildFilters($condition_group, $index, $type_fields);
    if (!empty($filters)) {
      $data['filters'] = $filters;
    }
    $facets = $this->setFacets($query, $type_fields);
    if (!empty($facets)) {
      $data['facets'] = $facets;
    }

    $response = $this->getClientService()->search($engine, $data);
    $results = $this->extractResults($query, $response);
    if ($facets = $this->extractFacets($query, $response, $type_fields, array_flip($field_names))) {
      $results->setExtraData('search_api_facets', $facets);
    }
  }

  /**
   * Extract results from Swiftype server response into search_api items.
   *
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   The search_api query.
   * @param array $response
   *   The server response including:
   *   - record_count: Total number of results.
   *   - records: List of search results.
   *   - info: Information about facets and results keyed by document type.
   *   - errors: List of errors.
   *
   * @see \Drupal\search_api_swiftype\SwiftypeClient\SwiftypeClientInterface::search()
   *
   * @return \Drupal\search_api\Query\ResultSetInterface
   *   A result set object.
   */
  protected function extractResults(QueryInterface $query, array $response = []) {
    $index = $query->getIndex();
    $fields = $index->getFields(TRUE);
    $fields += $this->getSpecialFields($index);
    // Get list of fields in index.
    $field_names = $this->getFieldNames($index);
    $id_field = $field_names['search_api_id'];
    $score_field = isset($field_names['search_api_relevance']) ? $field_names['search_api_relevance'] : '_score';
    $language_field = $field_names['search_api_language'];
    $skip_count = $query->getOption('skip result count');

    // Set up the results array.
    $result_set = $query->getResults();
    $result_set->setExtraData('search_api_swiftype_response', $response);
    $result_set->setResultCount($response['record_count']);

    if (empty($response['record_count'])) {
      if (!$skip_count) {
        $result_set->setResultCount(0);
      }
      return $result_set;
    }

    $result_count = 0;

    // Results are grouped by document type.
    foreach ($response['records'] as $document_type => $results) {
      foreach ($results as $result) {
        $item_id = $result[$id_field];
        $result_item = $this->fieldsHelper->createItem($index, $item_id);
        $result_item->setExtraData('search_api_swiftype_document', $result);
        $result_item->setExtraData('search_api_swiftype_document_type', $document_type);
        $result_item->setLanguage($result[$language_field]);

        if (isset($result[$score_field])) {
          $result_item->setScore($result[$score_field]);
          unset($result[$score_field]);
        }
        unset($result[$id_field]);

        // Extract fields.
        foreach ($field_names as $search_api_property => $swiftype_property) {
          if (!isset($result[$swiftype_property]) || !isset($fields[$search_api_property])) {
            continue;
          }
          $result_field = is_array($result[$swiftype_property]) ? $result[$swiftype_property] : [$result[$swiftype_property]];
          $field = clone $fields[$search_api_property];
          foreach ($result_field as &$value) {
            switch ($field->getType()) {
              case 'date':
                // Convert dates to timestamps.
                if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $value)) {
                  $value = strtotime($value);
                }
                break;

              case 'text':
                $value = new TextValue($value);
                break;
            }
          }
          $field->setValues($result_field);
          $result_item->setField($search_api_property, $field);
        }
        $result_set->addResultItem($result_item);
      }
      $result_count += $response['info'][$document_type]['total_result_count'];
    }

    if (!$skip_count) {
      $result_set->setResultCount($result_count);
    }

    return $result_set;
  }

  /**
   * Extract results from Swiftype server response into search_api items.
   *
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   The search_api query.
   * @param array $response
   *   The server response including:
   *   - record_count: Total number of results.
   *   - records: List of search results.
   *   - info: Information about facets and results keyed by document type.
   *   - errors: List of errors.
   * @param array $document_type_fields
   *   List of fields in document type.
   * @param array $field_names
   *   List of fields in index.
   *
   * @see \Drupal\search_api_swiftype\SwiftypeClient\SwiftypeClientInterface::search()
   *
   * @return array
   *   An array describing facets that apply to the current results.
   */
  protected function extractFacets(QueryInterface $query, array $response, array $document_type_fields = [], array $field_names = []) {
    $facets = [];

    $extract_facets = $query->getOption('search_api_facets', []);
    foreach (array_keys($document_type_fields) as $document_type) {
      if (empty($response['info'][$document_type]['facets'])) {
        continue;
      }
      foreach ($response['info'][$document_type]['facets'] as $facet_name => $facet_values) {
        if (!isset($field_names[$facet_name])) {
          continue;
        }
        $key = $field_names[$facet_name];
        if (empty($extract_facets[$key])) {
          continue;
        }
        if (empty($facets[$key])) {
          $facets[$key] = [];
        }
        foreach ($facet_values as $filter => $count) {
          $facets[$key][] = [
            'count' => $count,
            'filter' => $filter,
          ];
        }
      }
    }

    return $facets;
  }

  /**
   * Extract results from Swiftype server response into search_api items.
   *
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   The search_api query.
   * @param array $document_type_fields
   *   List of fields in document type.
   *
   * @see \Drupal\search_api_swiftype\SwiftypeClient\SwiftypeClientInterface::search()
   *
   * @return array
   *   An array describing facets that apply to the current results.
   */
  protected function setFacets(QueryInterface $query, array $document_type_fields = []) {
    $facets = [];
    $index = $query->getIndex();
    // Get list of fields in index.
    $field_names = $this->getFieldNames($index);

    $extract_facets = $query->getOption('search_api_facets', []);
    foreach ($extract_facets as $facet_info) {
      $field = $facet_info['field'];
      foreach (array_keys($document_type_fields) as $document_type) {
        if (!isset($document_type_fields[$document_type][$field_names[$field]])) {
          // Field does not exist in Swiftype document type.
          $this->logger->warning('Field @field is not defined in document type @type', ['@field' => $field, '@type' => $document_type]);
          continue;
        }
        if (empty($facets[$document_type])) {
          $facets[$document_type] = [];
        }
        $facets[$document_type][] = $field_names[$field];
      }
    }

    return $facets;
  }

  /**
   * {@inheritdoc}
   */
  public function buildSorts(QueryInterface $query) {
    $query_sorts = $query->getSorts();
    if (empty($query_sorts)) {
      return [];
    }
    if (!empty($query_sorts['search_api_relevance'])) {
      // Sorting by score is default behaviour.
      unset($query_sorts['search_api_relevance']);
    }

    $engine = $this->getEngineInfo();
    $index = $query->getIndex();
    $field_names = $this->getFieldNames($index);
    if (empty($field_names['search_api_id'])) {
      $field_names['search_api_id'] = 'external_id';
    }

    // Get all document types.
    $document_types_all = $this->getClientService()->listDocumentTypes($engine);
    $sort = [
      'sort_field' => [],
      'sort_direction' => [],
    ];
    foreach ($index->getDatasourceIds() as $datasource_id) {
      $document_type_keys = array_keys(array_column($document_types_all, 'name', 'slug'), $datasource_id, TRUE);
      $document_type = reset($document_type_keys);
      foreach ($query_sorts as $field_id => $direction) {
        if (!isset($field_names[$field_id])) {
          // The field doesn't exists for whatever reasons.
          $this->logger->warning('Field @field not found in index @index.', ['@field' => $field_id, '@index' => $index->id()]);
          continue;
        }
        $sort['sort_field'][$document_type] = $field_names[$field_id];
        $sort['sort_direction'][$document_type] = Unicode::strtolower($direction);

        // Unfortunately, Swiftype allows only one field for sorting.
        break;
      }
    }
    return $sort;
  }

  /**
   * {@inheritdoc}
   */
  public function buildFilters(ConditionGroupInterface $condition_group, IndexInterface $index, array $document_type_fields = []) {
    $conditions = $condition_group->getConditions();
    if (empty($conditions)) {
      return [];
    }

    $index_fields = $index->getFields(TRUE);
    $index_fields += $this->getSpecialFields($index);
    $field_names = $this->getFieldNames($index);
    $filters = [];

    foreach ($conditions as $condition) {
      if ($condition instanceof ConditionInterface) {
        $field = $condition->getField();
        if (!isset($index_fields[$field])) {
          // Field does not exist in search_api index.
          $this->logger->warning('Field @field not found in index @index.', ['@field' => $field, '@index' => $index->id()]);
          continue;
        }
        foreach (array_keys($document_type_fields) as $document_type) {
          if (!isset($document_type_fields[$document_type][$field_names[$field]])) {
            // Field does not exist in Swiftype document type.
            $this->logger->warning('Field @field is not defined in document type @type', ['@field' => $field, '@type' => $document_type]);
            continue;
          }
          if (empty($filters[$document_type])) {
            $filters[$document_type] = new \stdClass();
          }
          $value = $condition->getValue();
          if ($filter = $this->buildFilter($field_names[$field], $value, $condition->getOperator(), $index_fields[$field])) {
            $filters[$document_type]->{$field_names[$field]} = $filter;
          }
        }
      }
      else {
        // Nested condition group.
        $nested_filters = $this->buildFilters($condition, $index, $document_type_fields);
        // Merge filter objects.
        foreach (array_keys($document_type_fields) as $document_type) {
          if (empty($filters[$document_type])) {
            $filters[$document_type] = new \stdClass();
          }
          foreach ($nested_filters[$document_type] as $key => $value) {
            $filters[$document_type]->{$key} = $value;
          }
        }
      }
    }

    return $filters;
  }

  /**
   * {@inheritdoc}
   */
  public function buildFilter($field, $value, $operator, FieldInterface $index_field) {
    $filter = [];
    if (!is_array($value)) {
      $value = [$value];
    }
    $allowed_operators = ['=', '<>', 'IN', 'NOT IN'];
    foreach ($value as $value_item) {
      if (is_null($value_item) || in_array($operator, $allowed_operators)) {
        continue;
      }
      $value_item = trim($value_item);
      switch ($index_field->getType()) {
        case 'date':
          // Convert date to a format Swiftype understands.
          $value_item = date(\DateTime::ISO8601, $value_item);
          break;
      }
      $value_item = preg_replace('/("|\\\)/', '\\\$1', $value_item);
    }

    if (1 === count($value)) {
      $value = array_shift($value);
      // For a single value we can use simple comparison instead of a range.
      if ('IN' === $operator) {
        $operator = '=';
      }
      elseif ('NOT IN' === $operator) {
        $operator = '<>';
      }
    }

    switch ($operator) {
      case '<>':
        $filter = '!' . array_shift($value);
        break;

      case '<':
      case '<=':
        // Unfortunately Swiftype doesn't make a difference between included or
        // excluded bounds.
        $filter = [
          'type' => 'range',
          'to' => $value,
        ];
        break;

      case '>':
      case '>=':
        $filter = [
          'type' => 'range',
          'from' => $value,
        ];
        break;

      case 'BETWEEN':
        $filter = [
          'type' => 'range',
          'from' => array_shift($value),
          'to' => array_shift($value),
        ];
        break;

      case 'NOT BETWEEN':
        $filter = [
          'type' => 'range',
          'from' => '!' . array_shift($value),
          'to' => '!' . array_shift($value),
        ];
        break;

      case 'IN':
        $filter = array_values($value);
        break;

      case 'NOT IN':
        // Prepend every value with an exclamation mark.
        foreach ($value as $v) {
          $filter[] = '!' . $v;
        }
        break;

      case '=':
      default:
        $filter = $value;
        break;
    }

    return $filter;
  }

  /**
   * Flatten a keys array into a single search string.
   *
   * @param array $keys
   *   The keys array to flatten, formatted as specified by
   *   \Drupal\search_api\Query\QueryInterface::getKeys().
   *
   * @return string
   *   A query string representing the same keys.
   */
  protected function flattenKeys(array $keys) {
    $k = [];
    $pre = '+';

    if (isset($keys['#conjunction']) && $keys['#conjunction'] == 'OR') {
      $pre = '';
    }

    $neg = empty($keys['#negation']) ? '' : '-';

    foreach ($keys as $key_nr => $key) {
      // We cannot use \Drupal\Core\Render\Element::children() anymore because
      // $keys is not a valid render array.
      if ($key_nr[0] === '#' || !$key) {
        continue;
      }
      if (is_array($key)) {
        $subkeys = $this->flattenKeys($key);
        if ($subkeys) {
          $nested_expressions = TRUE;
          $k[] = "($subkeys)";
        }
      }
      else {
        $k[] = preg_replace('/("|\\\)/', '\\\$1', trim($key));
      }
    }
    if (!$k) {
      return '';
    }

    // Formatting the keys into a Solr query can be a bit complex. Keep in mind
    // that the default operator is OR. The following code will produce filters
    // that look like this:
    //
    // #conjunction | #negation | return value
    // ----------------------------------------------------------------
    // AND          | FALSE     | (+A +B +C)
    // AND          | TRUE      | -(+A +B +C)
    // OR           | FALSE     | (A B C)
    // OR           | TRUE      | -(A B C)
    //
    // If there was just a single, unnested key, we can ignore all this.
    if (count($k) == 1 && empty($nested_expressions)) {
      return $neg . reset($k);
    }

    return $neg . '(' . $pre . implode(' ' . $pre, $k) . ')';
  }

  /**
   * {@inheritdoc}
   */
  public function createDocumentFromItem(IndexInterface $index, ItemInterface $item) {
    $documents = $this->createDocumentsFromItems($index, [$item->getId() => $item]);
    return reset($documents);
  }

  /**
   * {@inheritdoc}
   */
  public function createDocumentsFromItems(IndexInterface $index, array &$items = []) {
    $factory = $this->getClientService()->getEntityFactory();
    $field_names = $this->getFieldNames($index);
    $index_id = $index->id();

    $documents = [];

    /** @var \Drupal\search_api\Item\ItemInterface[] $items */
    foreach ($items as $id => $item) {
      $document = $factory->createDocument($this->getClientService());
      $document->setExternalId($item->getId());
      $document->datasourceId = $item->getDatasourceId();
      $document->addField('index_id', $index_id);

      $special_fields = $this->getSpecialFields($index, $item);
      $item_fields = $item->getFields();
      $item_fields += $special_fields;
      /** @var \Drupal\search_api\Item\FieldInterface $field */
      foreach ($item_fields as $name => $field) {
        if (!isset($field_names[$name])) {
          $this->logger->warning('Field @field not found in index @index.', ['@field' => $name, '@index' => $index_id]);
          continue;
        }
        $document->addField($field_names[$name], $field->getValues(), $field->getType());
      }
      $documents[$id] = $document;
    }

    // Let other modules alter documents before sending them to the server.
    $this->moduleHandler->alter('search_api_swiftype_documents', $documents, $index, $items);

    return $documents;
  }

  /**
   * Get the internal field names for a search_api item.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The search_api index.
   *
   * @return string[]
   *   List of internal field names keyed by the items field names.
   */
  protected function getFieldNames(IndexInterface $index) {
    $fields = [];
    $cache_key = 'search_api_swiftype.fields';
    $index_id = $index->id();

    if ($cache = $this->cache->get($cache_key)) {
      $fields = $cache->data;
    }

    if (!empty($fields[$index_id])) {
      // Return cached list of fields.
      return $fields[$index_id];
    }

    $fields[$index_id] = [];
    $index_fields = $index->getFields(TRUE);
    $index_fields += $this->getSpecialFields($index);
    foreach ($index_fields as $key => $field) {
      if (isset($fields[$index_id][$key])) {
        // No need to process a field twice.
        continue;
      }
      $pref = '';
      if ($this->fieldsHelper->isFieldIdReserved($key)) {
        $pref .= 's';
      }
      else {
        if ($field->getDataDefinition()->isList() || $this->isHierarchicalField($field)) {
          $pref .= 'm';
        }
        else {
          try {
            $datasource = $field->getDatasource();
            if (!$datasource) {
              throw new SearchApiException();
            }
            else {
              $pref .= $this->getPropertyPathCardinality($field->getPropertyPath(), $datasource->getPropertyDefinitions()) != 1 ? 'm' : 's';
            }
          }
          catch (SearchApiException $e) {
            // Thrown by $field->getDatasource(). Assume multi value to be
            // safe.
            $pref .= 'm';
          }
        }
      }
      $name = $pref . '_' . $key;
      $fields[$index_id][$key] = $name;
    }

    if (empty($fields['search_api_relevance'])) {
      $fields[$index_id]['search_api_relevance'] = '_score';
    }

    $cache_tags = [
      "search_api_swiftype:api:{$this->configuration['api_key']}",
      "search_api_swiftype:index:{$index_id}",
      'search_api_swiftype:fields',
      "search_api_swiftype:fields:{$index_id}",
    ];

    // Let modules adjust the field mappings.
    $this->moduleHandler->alter('search_api_swiftype_field_mapping', $index, $fields);
    $this->cache->set($cache_key, $fields, CacheBackendInterface::CACHE_PERMANENT, $cache_tags);

    return $fields[$index_id];
  }

  /**
   * Computes the cardinality of a complete property path.
   *
   * @param string $property_path
   *   The property path of the property.
   * @param \Drupal\Core\TypedData\DataDefinitionInterface[] $properties
   *   The properties which form the basis for the property path.
   * @param int $cardinality
   *   The cardinality of the property path so far (for recursion).
   *
   * @see \Drupal\search_api_solr\Plugin\search_api\backend\SearchApiSolrBackend
   *
   * @return int
   *   The cardinality.
   */
  protected function getPropertyPathCardinality($property_path, array $properties, $cardinality = 1) {
    list($key, $nested_path) = Utility::splitPropertyPath($property_path, FALSE);
    if (!isset($properties[$key])) {
      return $cardinality;
    }
    $property = $properties[$key];
    if ($property instanceof FieldDefinitionInterface) {
      $storage = $property->getFieldStorageDefinition();
      if ($storage instanceof FieldStorageDefinitionInterface) {
        if ($storage->getCardinality() == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) {
          // Shortcut. We reached the maximum.
          return FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED;
        }
        $cardinality *= $storage->getCardinality();
      }
    }

    if (isset($nested_path)) {
      $property = $this->fieldsHelper->getInnerProperty($property);
      if ($property instanceof ComplexDataDefinitionInterface) {
        $cardinality = $this->getPropertyPathCardinality($nested_path, $this->fieldsHelper->getNestedProperties($property), $cardinality);
      }
    }
    return $cardinality;
  }

  /**
   * Checks if a field is (potentially) hierarchical.
   *
   * Fields are (potentially) hierarchical if:
   * - they point to an entity type; and
   * - that entity type contains a property referencing the same type of entity
   *   (so that a hierarchy could be built from that nested property).
   *
   * @see \Drupal\search_api\Plugin\search_api\processor\AddHierarchy::getHierarchyFields()
   * @see \Drupal\search_api_solr\Plugin\search_api\backend\SearchApiSolrBackend
   *
   * @return bool
   *   TRUE, if the field is hierarchical.
   */
  protected function isHierarchicalField(FieldInterface $field) {
    $definition = $field->getDataDefinition();
    if (!($definition instanceof ComplexDataDefinitionInterface)) {
      return FALSE;
    }
    $properties = $this->fieldsHelper->getNestedProperties($definition);
    // The property might be an entity data definition itself.
    $properties[''] = $definition;
    foreach ($properties as $property) {
      $property = $this->fieldsHelper->getInnerProperty($property);
      if (!($property instanceof EntityDataDefinitionInterface)) {
        continue;
      }
      if ($this->hasHierarchicalProperties($property)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Checks if hierarchical properties are nested on an entity-typed property.
   *
   * @param \Drupal\Core\Entity\TypedData\EntityDataDefinitionInterface $property
   *   The property to be searched for hierarchical nested properties.
   *
   * @see \Drupal\search_api\Plugin\search_api\processor\AddHierarchy::findHierarchicalProperties()
   * @see \Drupal\search_api_solr\Plugin\search_api\backend\SearchApiSolrBackend
   *
   * @return bool
   *   TRUE if the property is hierarchical.
   */
  protected function hasHierarchicalProperties(EntityDataDefinitionInterface $property) {
    $entity_type_id = $property->getEntityTypeId();

    // Check properties for potential hierarchy. Check two levels down, since
    // Core's entity references all have an additional "entity" sub-property for
    // accessing the actual entity reference, which we'd otherwise miss.
    foreach ($this->fieldsHelper->getNestedProperties($property) as $property_2) {
      $property_2 = $this->fieldsHelper->getInnerProperty($property_2);
      if (($property_2 instanceof EntityDataDefinitionInterface) && ($entity_type_id === $property_2->getEntityTypeId())) {
        return TRUE;
      }
      if (!($property_2 instanceof ComplexDataDefinitionInterface)) {
        return FALSE;
      }
      foreach ($property_2->getPropertyDefinitions() as $property_3) {
        $property_3 = $this->fieldsHelper->getInnerProperty($property_3);
        if (!($property_3 instanceof EntityDataDefinitionInterface) || ($entity_type_id !== $property_3->getEntityTypeId())) {
          continue;
        }
        return TRUE;
      }
    }
    return FALSE;
  }

}
