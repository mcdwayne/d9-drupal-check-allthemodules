<?php

namespace Drupal\elastic_search\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Url;
use Drupal\elastic_search\Elastic\ElasticIndexGenerator;
use Drupal\elastic_search\Elastic\ElasticIndexManager;
use Drupal\elastic_search\Entity\ElasticIndex;
use Drupal\elastic_search\Entity\ElasticIndexInterface;
use Drupal\elastic_search\Entity\FieldableEntityMap;
use Drupal\elastic_search\ValueObject\BatchDefinition;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class IndexController.
 * Controller containing batch callbacks related to Index management
 *
 * @package Drupal\elastic_search\Controller
 */
class IndexController extends ControllerBase {

  /**
   * @var \Drupal\elastic_search\Elastic\ElasticIndexManager
   */
  protected $indexManager;

  /**
   * @var EntityStorageInterface
   */
  protected $indexStorage;

  /**
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $loggerChannel;

  /**
   * @var \Drupal\elastic_search\Elastic\ElasticIndexGenerator
   */
  protected $indexGenerator;

  /**
   * @var int
   */
  protected $batchChunkSize;

  /**
   * Index chunk size is handled seperately as they cannot be created in bulk and large numbers will cause gateway
   * timeouts
   *
   * @var int
   */
  protected $indexChunkSize;

  /**
   * @inheritDoc
   */
  public function __construct(ElasticIndexManager $indexManager,
                              LoggerChannelInterface $loggerChannel,
                              EntityStorageInterface $indexStorage,
                              ElasticIndexGenerator $indexGenerator,
                              $batchSize,
                              $indexBatchSize
  ) {
    $this->indexManager = $indexManager;
    $this->loggerChannel = $loggerChannel;
    $this->indexStorage = $indexStorage;
    $this->indexGenerator = $indexGenerator;
    $this->batchChunkSize = (int) $batchSize;
    $this->indexChunkSize = (int) $indexBatchSize;
  }

  /**
   * @inheritDoc
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public static function create(ContainerInterface $container) {
    $conf = $container->get('config.factory')->get('elastic_search.server');
    return new static($container->get('elastic_search.indices.manager'),
                      $container->get('logger.factory')
                                ->get('elastic_search.indices.controller'),
                      $container->get('entity_type.manager')
                                ->getStorage('elastic_index'),
                      $container->get('elastic_search.indices.generator'),
                      $conf->get('advanced.batch_size'),
                      $conf->get('advanced.index_batch_size'));
  }

  /**
   * @return int
   */
  public function getBatchChunkSize(): int {
    return $this->batchChunkSize;
  }

  /**
   * @param int $batchChunkSize
   */
  public function setBatchChunkSize(int $batchChunkSize) {
    $this->batchChunkSize = $batchChunkSize;
  }

  /**
   * @return int
   */
  public function getIndexChunkSize(): int {
    return $this->indexChunkSize;
  }

  /**
   * @param int $indexChunkSize
   */
  public function setIndexChunkSize(int $indexChunkSize) {
    $this->indexChunkSize = $indexChunkSize;
  }

  /**
   * Update.
   *
   * @param \Drupal\elastic_search\Entity\ElasticIndexInterface $elastic_index
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function updateMapping(ElasticIndexInterface $elastic_index): RedirectResponse {
    return $this->updateMappings([$elastic_index]);
  }

  /**
   * UpdateIndices.
   *
   * Updates Indices on server
   *
   * @param array $elasticIndices
   *
   * @return null|\Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function updateMappings(array $elasticIndices = []) {

    if (empty($elasticIndices)) {
      $elasticIndices = $this->indexStorage->loadMultiple();
    }
    $chunks = array_chunk($elasticIndices, $this->indexChunkSize);
    return $this->executeBatch($chunks,
                               '\Drupal\elastic_search\Controller\IndexController::processUpdateMappingBatch',
                               '\Drupal\elastic_search\Controller\IndexController::finishBatch',
                               'update');
  }

  /**
   * @param array $indices
   * @param array $context
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   */
  public static function processUpdateMappingBatch(array $indices, array &$context) {

    if (!array_key_exists('progress', $context['sandbox'])) {
      $context['sandbox']['progress'] = 0;
    }

    //static function so cannot use DI :'(
    $indexManager = \Drupal::getContainer()->get('elastic_search.indices.manager');

    /** @var \Drupal\elastic_search\Entity\ElasticIndex $index */
    foreach ($indices as $index) {
      try {
        if ($indexManager->updateIndexOnServer($index)) {
          $indexManager->markIndexAsUpdated($index);
          drupal_set_message('Updated index: ' . $index->id());
        }
      } catch (\Throwable $t) {
        IndexController::printErrorMessage($t);
      }
      $context['sandbox']['progress']++;
      $context['results'][] = $index;
    }

    //Optional pause
    $serverConfig = \Drupal::config('elastic_search.server');
    if ($serverConfig->get('advanced.pause') !== NULL) {
      sleep((int) $serverConfig->get('advanced.pause'));
    }

  }

  /**
   * @param \Drupal\elastic_search\Entity\ElasticIndexInterface $elastic_index
   *
   * @return null|\Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function deleteIndex(ElasticIndexInterface $elastic_index) {
    return $this->deleteIndices([$elastic_index]);
  }

  /**
   * @param ElasticIndexInterface[] $elasticIndices
   *
   * @return null|\Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function deleteIndices(array $elasticIndices = []) {

    if (empty($elasticIndices)) {
      $elasticIndices = $this->indexStorage->loadMultiple();
    }
    $chunks = array_chunk($elasticIndices, $this->indexChunkSize);
    return $this->executeBatch($chunks,
                               '\Drupal\elastic_search\Controller\IndexController::processDeleteIndexBatch',
                               '\Drupal\elastic_search\Controller\IndexController::finishBatch',
                               'deletion');

  }

  /**
   * @param ElasticIndexInterface[] $elasticIndices
   * @param array                   $context
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function processDeleteIndexBatch(array $elasticIndices, array &$context) {

    if (!array_key_exists('progress', $context['sandbox'])) {
      $context['sandbox']['progress'] = 0;
    }

    //static function so cannot use DI :'(
    $indexManager = \Drupal::getContainer()->get('elastic_search.indices.manager');

    $deleteIndexEntity = FALSE;

    //Process the indices
    /** @var ElasticIndexInterface $index */
    foreach ($elasticIndices as $index) {
      $result = [
        'index_status'  => 'available',
        'entity_status' => 'available',
        'id'            => $index->id(),
      ];

      $future = $indexManager->deleteRemoteIndex($index);
      try {
        // access future's values, causing resolution if necessary
        if ($future['acknowledged']) {
          $deleteIndexEntity = TRUE;
          $result['index_status'] = 'deleted';
        }
      } catch (\Throwable $t) {
        $error = json_decode($t->getMessage());
        if ($error->status === 404) {
          //If it doesn't exist on the server we can still delete locally
          $deleteIndexEntity = TRUE;
          $result['index_status'] = '404';
        } else {
          //If it some other kind of error when we try to delete we should log it and we will not delete the local index
          $result['index_status'] = $t->getMessage();
        }
      }
      if ($deleteIndexEntity) {
        $index->delete();
        $result['entity_status'] = 'deleted';
      }
      drupal_set_message($index->id() . ' Status: ' . $result['index_status'] . ' Entity Status: ' .
                         $result['entity_status']);
      $context['sandbox']['progress']++;
      $context['results'][] = $result;
    }

    //Optional pause
    $serverConfig = \Drupal::config('elastic_search.server');
    if ($serverConfig->get('advanced.pause') !== NULL) {
      sleep((int) $serverConfig->get('advanced.pause'));
    }
  }

  /**
   * @param \Drupal\elastic_search\Entity\ElasticIndexInterface $elastic_index
   *
   * @return null|\Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function deleteRemoteIndex(ElasticIndexInterface $elastic_index) {

    return $this->executeBatch([[$elastic_index]],
                               '\Drupal\elastic_search\Controller\IndexController::processDeleteIndexRemoteBatch',
                               '\Drupal\elastic_search\Controller\IndexController::finishBatch',
                               'deletion');
  }

  /**
   * @param ElasticIndexInterface[] $elasticIndices
   * @param array                   $context
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function processDeleteIndexRemoteBatch(array $elasticIndices, array &$context) {

    if (!array_key_exists('progress', $context['sandbox'])) {
      $context['sandbox']['progress'] = 0;
    }

    //static function so cannot use DI :'(
    $indexManager = \Drupal::getContainer()->get('elastic_search.indices.manager');

    //Process the indices
    /** @var ElasticIndexInterface $index */
    foreach ($elasticIndices as $index) {
      $result = [
        'index_status'  => 'available',
        'entity_status' => 'available',
        'id'            => $index->id(),
      ];

      $future = $indexManager->deleteRemoteIndex($index);
      try {
        // access future's values, causing resolution if necessary
        if ($future['acknowledged']) {
          $result['index_status'] = 'deleted';
        }
      } catch (\Throwable $t) {
        $error = json_decode($t->getMessage());
        if ($error->status === 404) {
          $result['index_status'] = '404';
        } else {
          //If it some other kind of error when we try to delete we should log it and we will not delete the local index
          $result['index_status'] = $t->getMessage();
        }
      }
      drupal_set_message($index->id() . ' Status: ' . $result['index_status'] . ' Entity Status: ' .
                         $result['entity_status']);
      $context['sandbox']['progress']++;
      $context['results'][] = $result;
    }

    //Optional pause
    $serverConfig = \Drupal::config('elastic_search.server');
    if ($serverConfig->get('advanced.pause') !== NULL) {
      sleep((int) $serverConfig->get('advanced.pause'));
    }
  }

  /**
   * @param ElasticIndexInterface[] $elasticIndices
   *
   * @return null|\Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function deleteIndicesLocal(array $elasticIndices = []) {

    if (empty($elasticIndices)) {
      $elasticIndices = $this->indexStorage->loadMultiple();
    }
    //arbitrary value
    $chunks = array_chunk($elasticIndices, 50);
    return $this->executeBatch($chunks,
                               '\Drupal\elastic_search\Controller\IndexController::processDeleteLocalBatch',
                               '\Drupal\elastic_search\Controller\IndexController::finishBatch',
                               'deletion');

  }

  /**
   * @param ElasticIndexInterface[] $elasticIndices
   * @param array                   $context
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function processDeleteLocalBatch(array $elasticIndices, array &$context) {

    if (!array_key_exists('progress', $context['sandbox'])) {
      $context['sandbox']['progress'] = 0;
    }

    //Process the indices
    /** @var ElasticIndexInterface $index */
    foreach ($elasticIndices as $index) {
      $result = [
        'index_status'  => 'available',
        'entity_status' => 'available',
        'id'            => $index->id(),
      ];

      $index->delete();
      $result['entity_status'] = 'deleted';

      drupal_set_message($index->id() . ' Status: ' . $result['index_status'] . ' Entity Status: ' .
                         $result['entity_status']);
      $context['sandbox']['progress']++;
      $context['results'][] = $result;
    }

  }

  /**
   * @param \Throwable $t
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   */
  private static function printErrorMessage(\Throwable $t) {
    $message = $t->getMessage();
    $decoded = json_decode($message);
    if ($decoded) {
      drupal_set_message(t('Error: @type : @reason ',
                           [
                             '@type'   => $decoded->error->type,
                             '@reason' => $decoded->error->reason,
                           ]),
                         'error');
      $message = $decoded->error->type . ' <pre>' . print_r($decoded, TRUE) . '</pre>';
    } else {
      $message = t($t->getMessage());
      drupal_set_message($message);
    }
    return $message;
  }

  /**
   * Called as an action on the index list route
   *
   * @param array $maps
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\elastic_search\Exception\IndexGeneratorBundleNotFoundException
   *
   */
  public function generateIndexEntities(array $maps = []): RedirectResponse {

    $indices = $this->indexGenerator->generate($maps);

    $chunks = array_chunk($indices, $this->batchChunkSize);
    return $this->executeBatch($chunks,
                               '\Drupal\elastic_search\Controller\IndexController::processGenerateBatch',
                               '\Drupal\elastic_search\Controller\IndexController::finishBatch',
                               'creation');
  }

  /**
   * @param array $indices
   * @param array $context
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function processGenerateBatch(array $indices, array &$context) {

    if (!array_key_exists('progress', $context['sandbox'])) {
      $context['sandbox']['progress'] = 0;
    }
    
    /** @var ElasticIndex $index */
    foreach ($indices as $index) {
      $index->save();
      $context['sandbox']['progress']++;
      $context['results'][] = $index;
    }
    //As this is only local we do not offer the optional pause
  }

  /**
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function bulkClearIndexDocuments(): RedirectResponse {
    $indices = $this->indexStorage->loadMultiple();
    $chunks = array_chunk($indices, $this->batchChunkSize);
    return $this->executeBatch($chunks,
                               '\Drupal\elastic_search\Controller\IndexController::processClearBatch',
                               '\Drupal\elastic_search\Controller\IndexController::finishBatch',
                               'clearing');

  }

  /**
   * @param \Drupal\elastic_search\Entity\ElasticIndexInterface $elastic_index
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function clearIndexDocuments(ElasticIndexInterface $elastic_index): RedirectResponse {
    return $this->executeBatch([[$elastic_index]],
                               '\Drupal\elastic_search\Controller\IndexController::processClearBatch',
                               '\Drupal\elastic_search\Controller\IndexController::finishBatch',
                               'clearing');

  }

  /**
   * @param $indices
   * @param $context
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   */
  public static function processClearBatch($indices, &$context) {

    if (!array_key_exists('progress', $context['sandbox'])) {
      $context['sandbox']['progress'] = 0;
    }

    $indexManager = \Drupal::getContainer()->get('elastic_search.indices.manager');

    /** @var ElasticIndex $index */
    foreach ($indices as $index) {
      //TODO - response handling
      $indexManager->clearIndexDocuments($index->getIndexName(), $index->getIndexId());
      $context['sandbox']['progress']++;
    }
    //Optional pause
    $serverConfig = \Drupal::config('elastic_search.server');
    if ($serverConfig->get('advanced.pause') !== NULL) {
      sleep((int) $serverConfig->get('advanced.pause'));
    }

  }

  /**
   * @param \Drupal\elastic_search\Entity\ElasticIndexInterface $elastic_index
   *
   * @return null|\Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function documentUpdate(ElasticIndexInterface $elastic_index) {
    return $this->documentUpdates([$elastic_index]);
  }

  /**
   * @param array $elasticIndices
   *
   * @return null|\Symfony\Component\HttpFoundation\RedirectResponse
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function documentUpdates($elasticIndices = []) {

    if (empty($elasticIndices)) {
      $elasticIndices = $this->indexStorage->loadMultiple();
    }

    $processed = [];
    foreach ($elasticIndices as $elasticIndex) {
      //load fieldable entity map and skip update if child only
      /** @var \Drupal\elastic_search\Entity\FieldableEntityMapInterface $fm */
      $fm = FieldableEntityMap::load($elasticIndex->getMappingEntityId());
      if ($fm->isChildOnly()) {
        continue;
      }
      $entities = $this->indexManager->getDocumentsThatShouldBeInIndex($elasticIndex);
      $chunks = array_chunk($entities, $this->batchChunkSize);
      foreach ($chunks as &$chunk) {
        array_unshift($chunk, $elasticIndex);
      }
      $processed = array_merge($processed, $chunks);
    }

    return $this->executeBatch($processed,
                               '\Drupal\elastic_search\Controller\IndexController::processDocumentIndexBatch',
                               '\Drupal\elastic_search\Controller\IndexController::finishBatch',
                               'document update');
  }

  /**
   * @param array $entities
   * @param array $context
   *
   * @throws \Exception
   */
  public static function processDocumentIndexBatch(array $entities, array &$context) {

    //static function so cannot use DI :'(
    $documentManager = \Drupal::getContainer()->get('elastic_search.document.manager');

    $index = array_shift($entities);

    $documentManager->updateDocumentsByLanguage($entities, $index->getIndexLanguage());
    $context['results'][] = $index;
    //Optional pause
    $serverConfig = \Drupal::config('elastic_search.server');
    if ($serverConfig->get('advanced.pause') !== NULL) {
      sleep((int) $serverConfig->get('advanced.pause'));
    }

  }

  /**
   * @param array  $chunks
   * @param string $opCallback
   * @param string $finishCallback
   * @param string $messageKey
   *
   * @return null|\Symfony\Component\HttpFoundation\RedirectResponse
   */
  protected function executeBatch(array $chunks, string $opCallback, string $finishCallback, string $messageKey = '') {

    $ops = [];
    foreach ($chunks as $chunkedIndices) {
      $ops[] = [$opCallback, [$chunkedIndices]];
    }
    $batch = new BatchDefinition($ops,
                                 $finishCallback,
                                 $this->t('Processing index ' . $messageKey . ' batch'),
                                 $this->t('Index ' . $messageKey . ' is starting.'),
                                 $this->t('Processed @current out of @total.'),
                                 $this->t('Encountered an error.')
    );
    batch_set($batch->getDefinitionArray());
    return batch_process(Url::fromRoute('entity.elastic_index.collection'));

  }

  /**
   * @param bool  $success
   * @param array $results
   * @param       $operations
   */
  public static function finishBatch(bool $success, array $results, $operations) {

    if ($success) {
      // Here we do something meaningful with the results.
      $message = t('@count items processed', ['@count' => count($results)]);
      drupal_set_message($message);
    } else {
      // An error occurred.
      // $operations contains the operations that remained unprocessed.
      $error_operation = reset($operations);
      $message = t('An error occurred while processing %error_operation with arguments: @arguments',
                   ['%error_operation' => $error_operation[0], '@arguments' => print_r($error_operation[1], TRUE)]);
      drupal_set_message($message, 'error');
    }

  }

}
