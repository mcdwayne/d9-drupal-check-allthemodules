<?php

namespace Drupal\bridtv\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Queue\RequeueException;
use Drupal\Core\Queue\SuspendQueueException;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Performs synchronization of video data.
 *
 * @QueueWorker(
 *   id = "bridtv_sync",
 *   title = @Translation("Brid.TV video data synchronization")
 * )
 */
class BridSyncWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  static public function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory')->get('bridtv')
    );
  }

  /**
   * Constructs a BridSyncWorker object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param LoggerInterface $logger
   *   The logger instance.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $sync = $this->getSyncService();
    $consumer = $sync->getConsumer();

    if (!$consumer->isReady()) {
      throw new SuspendQueueException($this->t('The Brid.TV API consumer is not ready. Check the module configuration to be properly set up.'));
    }

    if (!empty($data['page'])) {
      $this->processRemoteList($data);
    }
    elseif (!empty($data['entity_ids'])) {
      $this->processEntities($data);
    }
    elseif (!empty($data['entity_id'])) {
      $data['entity_ids'] = [$data['entity_id']];
      $this->processEntities($data);
    }
    else {
      $this->logger->error($this->t('Invalid queue item received.'));
    }
  }

  /**
   * Get the sync service.
   *
   * @return \Drupal\bridtv\BridSync
   *   The sync service.
   */
  protected function getSyncService() {
    return \Drupal::service('bridtv.sync');
  }

  protected function processRemoteList($data) {
    $sync = $this->getSyncService();
    $consumer = $sync->getConsumer();

    if (!($videos_list = $consumer->getDecodedVideosList($data['page'], $sync::ITEMS_PER_QUEUE_ITEM))) {
      return;
    }

    if (empty($videos_list['Videos'])) {
      $this->logger->error($this->t('Missing or empty Videos parameter, aborting queue item processing.'));
      return;
    }
    if (empty($videos_list['Pagination'])) {
      $this->logger->error($this->t('Missing or empty Pagination parameter, aborting queue item processing.'));
      return;
    }

    $pagination = $videos_list['Pagination'];

    foreach ($videos_list['Videos'] as $video_data) {
      if (isset($video_data['Video']['id'])) {
        if (!$sync->syncVideoData($video_data['Video']['id'])) {
          throw new RequeueException($this->t('There was an error on synchronizing video items. The current step @current_page of @total_pages total steps is being re-queued for a next try.', ['@current_page' => $pagination['page'], '@total_pages' => $pagination['pageCount']]));
        };
      }
      else {
        throw new SuspendQueueException($this->t('Unexpected video data format given, aborting synchronization.'));
      }
    }

    $this->logger->info($this->t('Synced @num of @total remote Brid.TV video items (step @current_page of @total_pages total steps).', ['@num' => $pagination['current'], '@total' => $pagination['count'], '@current_page' => $pagination['page'], '@total_pages' => $pagination['pageCount']]));

    // In case we are not on the last page yet, throw the next
    // item into the queue to continue processing.
    if (!empty($data['q_next']) && !(empty($pagination['nextPage']) || ($pagination['nextPage'] == $pagination['page']))) {
      $sync->queueRemoteListItem($pagination['nextPage'], TRUE);
    }
  }

  protected function processEntities($data) {
    $sync = $this->getSyncService();
    $entity_resolver = $sync->getEntityResolver();

    foreach ($data['entity_ids'] as $entity_id) {
      $entity = $entity_resolver->getEntityStorage()->load($entity_id);
      $items = $entity_resolver->getFieldItemList($entity);
      if (!$items->isEmpty()) {
        if (!$sync->syncVideoData($items->first()->get('video_id')->getValue())) {
          throw new RequeueException($this->t('There was an error on synchronizing for entity ID @id of type @type.', ['@id' => $entity->id(), '@type' => $entity->getEntityTypeId()]));
        };
      }
    }

    $current_num = count($data['entity_ids']);
    $total_entities = $entity_resolver->getEntityQuery()->count()->execute();
    $this->logger->info($this->t('Synced @num of @total total @type entities with their remote Brid.TV video data.', ['@num' => $current_num, '@total' => $total_entities, '@type' => $entity_resolver->getEntityTypeDefinition()->getLabel()]));

    if (!empty($data['q_next'])) {
      $id_key = $entity_resolver->getEntityTypeDefinition()->getKey('id');
      $query = $entity_resolver->getEntityQuery();
      $query->sort($id_key, 'ASC');
      $query->condition($id_key, $entity->id(), '>');
      $query->range(0, $current_num);
      if ($result = $query->execute()) {
        $next_ids = array_values($result);
        $sync->queueMultipleEntitiesItem($next_ids, TRUE);
      }
    }
  }

}
