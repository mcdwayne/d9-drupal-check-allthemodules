<?php
/**
 * Created by PhpStorm.
 * User: twhiston
 * Date: 12.02.17
 * Time: 17:08
 */

namespace Drupal\elastic_search\Elastic;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\elastic_search\Entity\FieldableEntityMap;
use Drupal\elastic_search\Exception\ElasticDocumentBuilderSkipException;
use Drupal\elastic_search\Exception\IndexNotFoundException;
use Drupal\elastic_search\Plugin\QueueWorker\ElasticEntityUpdate;
use Drupal\elastic_search\ValueObject\QueueItem;
use Elasticsearch\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ElasticDocumentManager
 *
 * @package Drupal\elastic_search\Elastic
 */
class ElasticDocumentManager implements ContainerInjectionInterface {

  /**
   * @var  EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var Client
   */
  protected $elasticClient;

  /**
   * @var LoggerInterface
   */
  protected $logger;

  /**
   * @var PluginManagerInterface
   */
  protected $fieldMapperManager;

  /**
   * @var \Drupal\elastic_search\Elastic\BackReferenceProcessor
   */
  protected $backReferenceProcessor;

  /**
   * @var  \Drupal\elastic_search\Elastic\ElasticPayloadRenderer
   */
  protected $payloadRenderer;

  /**
   * @var bool
   */
  protected $queueUpdates;

  /**
   * @var bool
   */
  protected $queueInserts;

  /**
   * @var bool
   */
  protected $queueDeletes;

  /**
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * @var int
   */
  protected $batchSize;

  /**
   * @var int
   */
  protected $currentDepth = 0;

  /**
   * @var int
   */
  protected $activeMaxDepth = 1;

  /**
   * Entity types that are internal to elastic and should never be indexed
   */
  const ELASTIC_TYPES = [
    'elastic_analyzer',
    'fieldable_entity_map',
    'elastic_index',
  ];

  /**
   * ElasticDocumentBuilder constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface        $entityTypeManager
   * @param \Elasticsearch\Client                                 $elasticClient
   * @param \Psr\Log\LoggerInterface                              $logger
   * @param \Drupal\Component\Plugin\PluginManagerInterface       $fieldMapperManager
   * @param \Drupal\elastic_search\Elastic\BackReferenceProcessor $backReferenceProcessor
   * @param \Drupal\elastic_search\Elastic\ElasticPayloadRenderer $payloadRenderer
   * @param \Drupal\Core\Queue\QueueFactory                       $queueFactory
   * @param bool                                                  $queueUpdates
   * @param bool                                                  $queueInserts
   * @param bool                                                  $queueDeletes
   * @param string|int                                            $batchSize
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager,
                              Client $elasticClient,
                              LoggerInterface $logger,
                              PluginManagerInterface $fieldMapperManager,
                              BackReferenceProcessor $backReferenceProcessor,
                              ElasticPayloadRenderer $payloadRenderer,
                              QueueFactory $queueFactory,
                              $queueUpdates,
                              $queueInserts,
                              $queueDeletes,
                              $batchSize) {
    $this->entityTypeManager = $entityTypeManager;
    $this->elasticClient = $elasticClient;
    $this->logger = $logger;
    $this->fieldMapperManager = $fieldMapperManager;
    $this->backReferenceProcessor = $backReferenceProcessor;
    $this->payloadRenderer = $payloadRenderer;
    $this->queueFactory = $queueFactory;
    $this->queueUpdates = (bool) $queueUpdates;
    $this->queueInserts = (bool) $queueInserts;
    $this->queueDeletes = (bool) $queueDeletes;
    $this->batchSize = (int) $batchSize;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *
   * @return static
   *
   * @throws \Exception
   */
  public static function create(ContainerInterface $container) {
    $conf = $container->get('config.factory')->get('elastic_search.server');
    return new static(
      $container->get('entity_type.manager'),
      $container->get('elastic_search.connection_factory')
                ->getElasticConnection(),
      $container->get('logger.factory')->get('elastic.document.manager'),
      $container->get('plugin.manager.elastic_field_mapper_plugin'),
      $container->get('elastic_search.backreference_processor'),
      $container->get('elastic_search.document.renderer'),
      $container->get('queue'),
      $conf->get('advanced.queue_update'),
      $conf->get('advanced.queue_insert'),
      $conf->get('advanced.queue_delete'),
      $conf->get('advanced.batch_size')

    );
  }

  /**
   * @return \Drupal\elastic_search\Elastic\ElasticPayloadRenderer
   */
  public function getPayloadRenderer(): ElasticPayloadRenderer {
    return $this->payloadRenderer;
  }

  /**
   * Insert actions don't bother checking for back references as they should not exist with a totally new node
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function insertAction(EntityInterface $entity) {

    /** @var \Drupal\elastic_search\Entity\FieldableEntityMapInterface $fem */
    $fem = $this->entityTypeManager->getStorage('fieldable_entity_map')
                                   ->load(FieldableEntityMap::getMachineName($entity->getEntityTypeId(),
                                                                             $entity->bundle()));

    if (!$this->shouldIndex($entity, $fem) || $fem->isChildOnly()) {
      return;
    }

    if ($this->queueInserts) {
      /** @var \Drupal\Core\Queue\QueueInterface $queue */
      $queue = $this->queueFactory->get('elastic_entity_update');
      $queue->createItem([QueueItem::NewFromEntity($entity)]);
      $this->logger->info('Queued insert for document @id', ['@id' => $entity->id()]);
    } else {
      //if not queue send now
      self::updateDocuments([$entity], $this, $this->logger);
    }
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function updateAction(EntityInterface $entity) {

    /** @var \Drupal\elastic_search\Entity\FieldableEntityMapInterface $fem */
    $fem = $this->entityTypeManager->getStorage('fieldable_entity_map')
                                   ->load(FieldableEntityMap::getMachineName($entity->getEntityTypeId(),
                                                                             $entity->bundle()));

    if (!$this->shouldIndex($entity, $fem)) {
      return;
    }

    /**
     * Array of queue items indexed by their uuid
     *
     * @var QueueItem[] $queueItems
     */
    $queueItems = [];

    if (!$fem->isChildOnly()) {
      //If the type is not child only we add it to the queue. If it is only get the backreferences and update those
      $queueItems[$entity->uuid()] = QueueItem::NewFromEntity($entity);
    }

    $this->setMaxBackReferencesDepth($entity);
    //check for back references, add them to entityList

    $this->getBackReferences($entity, $queueItems);

    //now that we have an array of references that we need we want to chunk it up, so we can do bulk updates
    $chunks = array_chunk($queueItems, $this->batchSize);

    if ($this->queueUpdates) {
      /** @var \Drupal\Core\Queue\QueueInterface $queue */
      $queue = $this->queueFactory->get('elastic_entity_update');
      foreach ($chunks as $chunk) {
        $queue->createItem($chunk);
      }
    } else {
      //This is not exactly super efficient as we already had all the items when building the queue
      //But this way is not efficient or recommended anyway, queue it up!
      foreach ($chunks as $chunk) {
        $hydrated = ElasticEntityUpdate::hydrateItems($chunk);
        self::updateDocuments($hydrated, $this, $this->logger);
      }

    }

  }

  /**
   * Deletes the main entity and updates all back references to it
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function deleteAction(EntityInterface $entity) {

    /** @var \Drupal\elastic_search\Entity\FieldableEntityMapInterface $fem */
    $fem = $this->entityTypeManager->getStorage('fieldable_entity_map')
                                   ->load(FieldableEntityMap::getMachineName($entity->getEntityTypeId(),
                                                                             $entity->bundle()));

    if (!$this->shouldIndex($entity, $fem)) {
      return;
    }

    /**
     * Array of queue items indexed by their uuid
     *
     * @var QueueItem[] $queueItems
     */
    $queueItems = [];
    $this->setMaxBackReferencesDepth($entity);

    //check for back references, add them to entityList
    $this->getBackReferences($entity, $queueItems);

    //now that we have an array of references that we need we want to chunk it up, so we can do bulk updates
    $chunks = array_chunk($queueItems, $this->batchSize);

    if ($this->queueUpdates) {
      /** @var \Drupal\Core\Queue\QueueInterface $queue */
      $queue = $this->queueFactory->get('elastic_entity_delete');
      $queue->createItem([QueueItem::NewFromEntity($entity)]);

      $queue = $this->queueFactory->get('elastic_entity_update');
      foreach ($chunks as $chunk) {
        $queue->createItem($chunk);
      }
    } else {

      //Delete top level immediately
      if (!$fem->isChildOnly()) {
        self::deleteDocuments([$entity], $this, $this->logger);
      }
      //This is not exactly super efficient as we already had all the items when building the queue
      //But this way is not efficient or recommended anyway, queue it up!
      foreach ($chunks as $chunk) {
        $hydrated = ElasticEntityUpdate::hydrateItems($chunk);
        self::updateDocuments($hydrated, $this, $this->logger);
      }
    }
  }

  /**
   * Sets the max depth to get the back references of a given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   */
  protected function setMaxBackReferencesDepth(EntityInterface $entity) {
    try {
      $fieldMap = FieldableEntityMap::load(FieldableEntityMap::getMachineName($entity->getEntityTypeId(),
                                                                              $entity->bundle()));
      $depth = $fieldMap->getRecursionDepth() ? $fieldMap->getRecursionDepth() :
        1;
    } catch (\Exception $e) {
      $depth = 1;
    }

    $this->activeMaxDepth = $depth;
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param array                               $entityList
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function getBackReferences(EntityInterface $entity,
                                       array &$entityList) {

    if ($this->currentDepth > $this->activeMaxDepth) {
      return;
    }
    ++$this->currentDepth;

    /** @var \Drupal\field\Entity\FieldConfig[] $rfs */
    $rfs = $this->backReferenceProcessor->referencingFields($entity->getEntityTypeId(), $entity->bundle());
    foreach ($rfs as $rf) {
      $refEnts = $this->backReferenceProcessor->loadReferencingEntities($rf, $entity->id());
      /** @var EntityInterface $refEnt */
      foreach ($refEnts as $refEnt) {

        try {
          //Make sure we have the correct translation if the type supports translations
          $translation = $refEnt->getTranslation($entity->language()->getId());
        } catch (\Throwable $t) {
          $translation = $refEnt;
        }

        //We also must test to see if this type should be indexed. Does it have an FEM?
        $fem = FieldableEntityMap::load(FieldableEntityMap::getMachineName($translation->getEntityTypeId(),
                                                                           $translation->bundle()));

        if (!$this->shouldIndex($translation, $fem) || array_key_exists($translation->uuid(), $entityList)) {
          continue;
        }

        if (!$fem->isChildOnly()) {
          //If the type is not child only we add it to the queue. If it is be only get the backreferences and update those
          $item = QueueItem::NewFromEntity($translation);
          $entityList[$translation->uuid()] = $item;
        }

        //Each translation also needs to be checked for back references
        $this->getBackReferences($translation, $entityList);
      }
    }
    --$this->currentDepth;
  }

  /**
   * Check core conditions of allowing indexing
   *
   * @param \Drupal\Core\Entity\EntityInterface                            $entity
   * @param \Drupal\elastic_search\Entity\FieldableEntityMapInterface|null $fem
   *
   * @return bool
   */
  private function shouldIndex(EntityInterface $entity, $fem): bool {

    if (in_array($entity->getEntityTypeId(), self::ELASTIC_TYPES, TRUE)) {
      return FALSE;
    }

    //We also must test to see if this type should be indexed. Does it have an FEM?
    if (!$fem) {
      return FALSE;
    }

    if (!$fem->isActive()) {
      return FALSE;
    }

    return TRUE;

  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return bool
   */
  public static function isElasticTypeEntity(EntityInterface $entity) {
    return in_array($entity->getEntityTypeId(), self::ELASTIC_TYPES, TRUE);
  }

  /**
   * @param string $entityTypeId
   *
   * @return bool
   */
  public static function isElasticType(string $entityTypeId) {
    return in_array($entityTypeId, self::ELASTIC_TYPES, TRUE);
  }

  /**
   * Update a list of documents
   *
   * @param EntityInterface[]                                     $entities
   * @param \Drupal\elastic_search\Elastic\ElasticDocumentManager $documentManager
   */
  public static function updateDocuments(array $entities,
                                         ElasticDocumentManager $documentManager,
                                         LoggerInterface $logger) {

    $payloads = ['body' => []];
    /** @var \Drupal\menu_link_content\Entity\MenuLinkContent $entity */
    foreach ($entities as $entity) {
      $entityString = $entity->id() . ' ' . $entity->getEntityTypeId() . ' ' . $entity->bundle() . ' ' .
                      $entity->uuid();
      try {
        $payload = $documentManager->getPayloadRenderer()->buildDocumentPayload($entity);
      } catch (ElasticDocumentBuilderSkipException $e) {
        $logger->notice('Skipped inserting document: ' . $entityString . '    ' . json_encode($e->getMessage()));
        return;
      } catch (\Exception $e) {
        $logger->critical('Could not insert document: ' . $entityString . '    ' . json_encode($e->getMessage()));
        return;
      }
      $payloads['body'] = array_merge($payloads['body'], $payload['body']);
    }

    if (!empty($payloads['body'])) {
      $payloads['client'] = ['timeout' => 5, 'future' => 'lazy'];
      try {
        $documentManager->sendDocuments($payloads);
        $logger->info('Updated document set');
      } catch (\Throwable $t) {
        $logger->critical('Could not send document' . json_encode($t->getMessage()));
      }
    }

  }

  /**
   * Update a set of documents, with a specific shared language, to the server
   * This should be used when you can guarantee the language of the entity that you wish to load, as this will attempt
   * to load the correct translation If no translation can be found it will use the original object (for untranslatable
   * fields)
   *
   * @param array  $entities
   * @param string $language
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\elastic_search\Exception\ElasticDocumentBuilderSkipException
   * @throws \Drupal\elastic_search\Exception\ElasticDocumentManagerRecursionException
   * @throws \Drupal\elastic_search\Exception\IndexNotFoundException
   * @throws \Drupal\elastic_search\Exception\MapNotFoundException
   */
  public function updateDocumentsByLanguage(array $entities, string $language) {
    $payloads = ['body' => []];
    /** @var \Drupal\menu_link_content\Entity\MenuLinkContent $entity */
    foreach ($entities as $entity) {
      try {
        $translation = $entity->getTranslation($language);
      } catch (\Throwable $t) {
        $translation = $entity;
      }
      $payload = $this->payloadRenderer->buildDocumentPayload($translation);
      $payloads['body'] = array_merge($payloads['body'], $payload['body']);
      $payloads['client'] = ['timeout' => 5, 'future' => 'lazy'];
    }
    if (!empty($payloads['body'])) {
      $this->sendDocuments($payloads);
    }
  }

  /**
   * @param QueueItem[]                                           $data
   * @param \Drupal\elastic_search\Elastic\ElasticDocumentManager $documentManager
   * @param \Psr\Log\LoggerInterface                              $logger
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\elastic_search\Exception\ElasticDocumentBuilderSkipException
   * @throws \Drupal\elastic_search\Exception\ElasticDocumentManagerRecursionException
   * @throws \Drupal\elastic_search\Exception\IndexNotFoundException
   * @throws \Drupal\elastic_search\Exception\MapNotFoundException
   */
  public static function deleteOrphanedDocumentsFromQueue(array $orphans,
                                                          ElasticDocumentManager $documentManager,
                                                          LoggerInterface $logger) {

    $payloads = ['body' => []];

    /** @var QueueItem $orphan */
    foreach ($orphans as $orphan) {

      $femName = FieldableEntityMap::getMachineName($orphan->getEntityType(), $orphan->getBundle());
      /** @var \Drupal\elastic_search\Entity\FieldableEntityMapInterface $fem */
      $fem = $documentManager->entityTypeManager->getStorage('fieldable_entity_map')->load($femName);

      try {
        $payload = $documentManager->getPayloadRenderer()
                                   ->buildDeleteOrphanPayload($orphan->getUuid(), $orphan->getLanguage(), $fem);
      } catch (IndexNotFoundException $e) {
        //Only catch IndexNotFound Exceptions as these cannot be resolved and should be removed from the queue to not back up deletion data
        continue;
      }

      $payloads['body'] = array_merge($payloads['body'], $payload['body']);
    }

    if (!empty($payloads['body'])) {
      $payloads['client'] = ['timeout' => 5, 'future' => 'lazy'];
      try {
        $documentManager->sendDocuments($payloads);
        $logger->info('Updated document set');
      } catch (\Throwable $t) {
        $logger->critical('Could not send document' . json_encode($t->getMessage()));
      }
    }

  }

  /**
   * Deletes a list of documents, the documents MUST STILL EXIST for this to be successful
   * If you need to delete a document that has already been deleted from your local system please call
   * deleteOrphanedDocuments
   *
   * @param string[]                                              $uuids
   * @param \Drupal\elastic_search\Elastic\ElasticDocumentManager $documentManager
   */
  public static function deleteDocuments(array $entities,
                                         ElasticDocumentManager $documentManager,
                                         LoggerInterface $logger) {

    $payloads = ['body' => []];
    /** @var \Drupal\menu_link_content\Entity\MenuLinkContent $entity */
    foreach ($entities as $entity) {
      $entityString = $entity->id() . ' ' . $entity->getEntityTypeId() . ' ' . $entity->bundle() . ' ' .
                      $entity->uuid();
      try {
        $payload = $documentManager->getPayloadRenderer()->buildDeletePayload($entity);
      } catch (ElasticDocumentBuilderSkipException $e) {
        $logger->notice('Skipped inserting document: ' . $entityString . '    ' . json_encode($e->getMessage()));
        return;
      } catch (\Exception $e) {
        $logger->critical('Could not insert document: ' . $entityString . '    ' . json_encode($e->getMessage()));
        return;
      }
      $payloads['body'] = array_merge($payloads['body'], $payload['body']);
    }

    if (!empty($payloads['body'])) {
      $payloads['client'] = ['timeout' => 5, 'future' => 'lazy'];
      try {
        $documentManager->sendDocuments($payloads);
        $logger->info('Updated document set');
      } catch (\Throwable $t) {
        $logger->critical('Could not send document' . json_encode($t->getMessage()));
      }
    }
  }

  /**
   * @param array $payloads
   *
   * @return bool
   */
  private function sendDocuments(array $payloads): bool {
    $response = $this->elasticClient->bulk($payloads);
    if ($response['errors']) {
      $this->logger->warning(json_encode($response));
    }
    return !$response['errors'];
  }

}