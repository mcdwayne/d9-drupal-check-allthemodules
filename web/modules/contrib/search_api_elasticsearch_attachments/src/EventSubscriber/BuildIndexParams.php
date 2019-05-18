<?php

namespace Drupal\search_api_elasticsearch_attachments\EventSubscriber;

use Drupal\elasticsearch_connector\Event\BuildIndexParamsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\search_api\Entity\Index;
use Drupal\search_api_elasticsearch_attachments\Helpers;
use Drupal\elasticsearch_connector\ElasticSearch\ClientManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\search_api\IndexInterface;
use Elasticsearch\Common\Exceptions\Missing404Exception;

/**
 * {@inheritdoc}
 */
class BuildIndexParams implements EventSubscriberInterface {

  protected $pipelineName = 'es_attachment';
  protected $targetFieldId = 'es_attachment';

  /**
   * {@inheritdoc}
   */
  public function __construct(ClientManagerInterface $client_manager, EntityTypeManagerInterface $entity_type_manager) {
    $this->clientManager = $client_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[BuildIndexParamsEvent::BUILD_PARAMS][] = ['indexParams', 100];
    $events[BuildIndexParamsEvent::BUILD_PARAMS][] = ['pipelineProcessing', 101];
    return $events;
  }

  /**
   * Method to build Params.
   *
   * @param \Drupal\elasticsearch_connector\Event\BuildIndexParamsEvent $event
   *   The BuildIndexParamsEvent event.
   */
  public function indexParams(BuildIndexParamsEvent $event) {
    // We need to react only on our processor.
    $indexName = $this->getIndexName($event);
    $processors = $this->getIndexProcessors($indexName);
    // Add pipeline param.
    if (!empty($processors['elasticsearch_attachments'])) {
      $params = $event->getElasticIndexParams();
      // Add pipeline param for attachment processing.
      $params['pipeline'] = $this->pipelineName;
      // Set updated params array.
      $event->setElasticIndexParams($params);
    }
  }

  /**
   * Valdiate pipeline. Create new one or delete existing.
   *
   * @param \Drupal\elasticsearch_connector\Event\BuildIndexParamsEvent $event
   *   The BuildIndexParamsEvent event.
   */
  public function pipelineProcessing(BuildIndexParamsEvent $event) {
    // Get incex name and list of available processors.
    $indexName = $this->getIndexName($event);
    $processors = $this->getIndexProcessors($indexName);
    /** @var \Drupal\search_api\IndexInterface $index */
    $index = $this->getIndex($indexName);
    // Initialize client to work with.
    $this->initializeClient($index);
    // Pipeline registration.
    if (!empty($processors['elasticsearch_attachments'])) {
      // If there is no pipeline yet, Elastic will return Missing404Exception.
      // There is no other way to check if pipeline exist.
      try {
        $this->getPipeline();
      }
      catch (Missing404Exception $e) {
        $this->putPipeline();
      }
    }
    else {
      // If there is no pipeline yet, Elastic will return Missing404Exception.
      // There is no other way to check if pipeline exist.
      try {
        $this->getPipeline();
        $this->deletePipeline();
      }
      catch (Missing404Exception $e) {
        // Nothing to do here.
      }
    }
  }

  /**
   * Get index name.
   *
   * @param \Drupal\elasticsearch_connector\Event\BuildIndexParamsEvent $event
   *   The BuildIndexParamsEvent event.
   *
   * @return string
   *   Index name
   */
  public function getIndexName(BuildIndexParamsEvent $event) {
    return Helpers::getIndexName($event->getIndexName());
  }

  /**
   * Get list of all available index processors.
   *
   * @param string $indexName
   *   Name of index.
   *
   * @return array
   *   List of all available processors.
   */
  public function getIndexProcessors($indexName) {
    return Index::load($indexName)->getProcessors();
  }

  /**
   * Get list of all available index processors.
   *
   * @param string $indexName
   *   Name of index.
   *
   * @return \Drupal\search_api\IndexInterface
   *   Index object.
   */
  public function getIndex($indexName) {
    return Index::load($indexName);
  }

  /**
   * ElasticSearch client initialization.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The index scheduled for indexing.
   */
  public function initializeClient(IndexInterface $index) {
    $cluster_name = $index->getServerInstance()->getBackend()->getCluster();
    $cluster = $this->entityTypeManager->getStorage('elasticsearch_cluster')->load($cluster_name);
    $this->client = $this->clientManager->getClientForCluster($cluster);
  }

  /**
   * Helper to register new pipeline.
   */
  public function putPipeline() {
    $params = [];
    $params['id'] = $this->pipelineName;
    $params['body'] = [
      'description' => 'Extract attachment information from arrays',
      'processors' => [
        [
          'foreach' => [
            'field' => $this->targetFieldId,
            'ignore_failure' => TRUE,
            'processor' => [
              'attachment' => [
                'target_field' => '_ingest._value.attachment',
                'field' => '_ingest._value.data',
              ],
            ],
          ],
        ],
      ],
    ];
    $this->client->ingest()->putPipeline($params);
  }

  /**
   * Helper to delete exiting pipeline.
   */
  public function deletePipeline() {
    $this->client->ingest()->deletePipeline(['id' => $this->pipelineName]);
  }

  /**
   * Helper to get exiting pipeline.
   */
  public function getPipeline() {
    return $this->client->ingest()->getPipeline(['id' => $this->pipelineName]);
  }

}
