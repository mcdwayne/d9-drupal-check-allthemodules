<?php

namespace Drupal\bridtv;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueWorkerManagerInterface;
use Drupal\Core\Queue\RequeueException;
use Drupal\Core\Queue\SuspendQueueException;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Service class for handling synchronization of video data.
 */
class BridSync {

  use StringTranslationTrait;

  /**
   * Lease time of claimed queue items.
   */
  const LEASE_TIME = 60;

  /**
   * The default number of video items to process per queue item.
   */
  const ITEMS_PER_QUEUE_ITEM = 5;

  /**
   * The queue holding sync items.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $queue;

  /**
   * The corresponding queue worker.
   *
   * @var \Drupal\Core\Queue\QueueWorkerInterface
   */
  protected $worker;

  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $settings;

  /**
   * @var \Drupal\bridtv\BridApiConsumer
   */
  protected $consumer;

  /**
   * The key value storage.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $keyValue;

  /**
   * @var \Drupal\bridtv\BridEntityResolver
   */
  protected $entityResolver;

  protected $autocreateEnabled;
  protected $autodeleteEnabled;

  /**
   * The logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  public function __construct(ConfigFactoryInterface $config_factory, QueueFactory $queue_factory, QueueWorkerManagerInterface $worker_manager, LoggerChannelFactoryInterface $logger_factory, BridApiConsumer $consumer, BridEntityResolver $entity_resolver, KeyValueFactoryInterface $kv_factory) {
    $this->settings = $config_factory->get('bridtv.settings');
    $this->queue = $queue_factory->get('bridtv_sync');
    $this->worker = $worker_manager->createInstance('bridtv_sync');
    $this->logger = $logger_factory->get('bridtv');
    $this->consumer = $consumer;
    $this->entityResolver = $entity_resolver;
    $this->keyValue = $kv_factory->get('bridtv');
    $this->autocreateEnabled(!empty($this->settings->get('sync_autocreate')));
    $this->autodeleteEnabled(!empty($this->settings->get('sync_autodelete')));
  }

  public function syncVideoData($id) {
    if (!$this->consumer->isReady()) {
      $this->logger->error($this->t('The Brid.TV API consumer is not ready. Check the module configuration to be properly set up.'));
      return FALSE;
    }

    $entity = $this->entityResolver->getEntityForVideoId($id);

    $is_new = FALSE;
    if (!$entity) {
      if ($this->autocreateEnabled()) {
        $entity = $this->entityResolver->newEntity();
        $is_new = TRUE;
      }
      else {
        return TRUE;
      }
    }

    $status = NULL;
    $latest_data = $this->consumer->fetchVideoData($id, $status);

    if ($latest_data && ($decoded = BridSerialization::decode($latest_data))) {
      if (empty($decoded['Video'])) {
        $this->logger->error($this->t('Unexpected Video data retrieved, aborting synchronization.'));
        return FALSE;
      }

      $video = $decoded['Video'];
      $items = $this->entityResolver->getFieldItemList($entity);
      $new_value = [
        'video_id' => $id,
        'title' => !empty($video['name']) ? $video['name'] : NULL,
        'description' => !empty($video['description']) ? $video['description'] : NULL,
        'publish_date' => !empty($video['publish']) ? $video['publish'] : NULL,
        'data' => $latest_data,
      ];

      if ($items->isEmpty() || !($items->first()->get('data')->getValue() == $latest_data)) {
        $items->setValue([$new_value]);
        $entity->save();
        if ($is_new) {
          $this->logger->notice($this->t('Saved a new @type entity with ID @id for a newly added Brid.TV video (@video_id).', ['@type' => $entity->getEntityType()->getLabel(),'@id' => $entity->id(), '@video_id' => $id]));
        }
        else {
          $this->logger->notice($this->t('Updated @type entity with ID @id to be synchronized with its remote Brid.TV video (@video_id).', ['@type' => $entity->getEntityType()->getLabel(),'@id' => $entity->id(), '@video_id' => $id]));
        }
      }
    }

    elseif (!$latest_data && !$entity->isNew() && ($status == 404) && $this->autodeleteEnabled()) {
      $entity->delete();
      $this->logger->notice($this->t('Deleted @type entity with ID @id, because its referenced Brid.TV does not exist anymore. To prevent automatic deletions, disable the autodelete setting at the Brid.TV module settings.', ['@type' => $entity->getEntityType()->getLabel(),'@id' => $entity->id()]));
    }

    else {
      return FALSE;
    }

    return TRUE;
  }

  public function syncVideoDataForEntity(FieldableEntityInterface $entity) {
    $field = NULL;
    foreach ($entity->getFieldDefinitions() as $definition) {
      if ($definition->getType() === 'bridtv') {
        $field = $definition->getName();
        break;
      }
    }
    if (!$field || $entity->get($field)->isEmpty() || !($video_id = $entity->get($field)->first()->get('video_id')->getValue())) {
      return FALSE;
    }

    $representing = $this->entityResolver->getEntityForVideoId($video_id);
    if (!(($representing->getEntityTypeId() === $entity->getEntityTypeId()) && ($representing->id() === $entity->id()))) {
      return FALSE;
    }

    return $this->syncVideoData($video_id);
  }

  public function syncPlayersInfo() {
    $keep = [];
    foreach (['players_list', 'players_data'] as $key) {
      if ($value = $this->keyValue->get($key)) {
        $keep[$key] = $value;
      }
    };
    $this->keyValue->deleteAll();
    foreach ($keep as $key => $value) {
      $this->keyValue->set($key, $value);
    }

    $consumer = $this->consumer;
    if ($fetched = $consumer->fetchPlayersList()) {
      if (BridSerialization::decode($fetched)) {
        $this->keyValue->set('players_list', $fetched);
      }
    }
    if ($fetched = $consumer->fetchPlayersDataList()) {
      if (BridSerialization::decode($fetched)) {
        $this->keyValue->set('players_data', $fetched);
      }
    }
  }

  public function run($limit = -1) {
    $limit = (int) $limit;
    $this->prepareFullSync();
    for ($i = 0; $i !== $limit; $i++) {
      if (!$this->processNextItem()) {
        break;
      };
    }
  }

  public function prepareFullSync() {
    if (!$this->consumer->isReady()) {
      $this->logger->error($this->t('The Brid.TV API consumer is not ready. Check the module configuration to be properly set up.'));
      return;
    }

    // Synchronize all player information.
    $this->syncPlayersInfo();

    $queue = $this->queue;
    $queue->createQueue();
    if ($queue->numberOfItems() > 0) {
      // Queue is not empty, i.e. synchronization is already in progress.
      $this->logger->info($this->t('The queue for synchronizing Brid.TV video data contains items for processing.'));
      return;
    }

    // When autodelete is enabled, we need
    // to sync the other way around (from local to remote).
    if ($this->autodeleteEnabled()) {
      $query = $this->entityResolver->getEntityQuery();
      $query->sort($this->entityResolver->getEntityTypeDefinition()->getKey('id'), 'ASC');
      $query->range(0, static::ITEMS_PER_QUEUE_ITEM);
      if ($result = $query->execute()) {
        $entity_ids = array_values($result);
        $this->queueMultipleEntitiesItem($entity_ids, TRUE);
      }
    }

    $this->queueRemoteListItem(1, TRUE);

    $this->logger->info($this->t('The full synchronization of Brid.TV video data has been enqueued for processing.'));
  }

  public function processNextItem() {
    $queue = $this->queue;
    $worker = $this->worker;

    if (!($item = $queue->claimItem(static::LEASE_TIME))) {
      $this->logger->info($this->t('No more items in the queue for processing synchronization.'));
      return FALSE;
    }

    try {
      $worker->processItem($item->data);
      $queue->deleteItem($item);
      return TRUE;
    }
    catch (RequeueException $e) {
      // The worker requested the task to be immediately requeued.
      $queue->releaseItem($item);
      return TRUE;
    }
    catch (SuspendQueueException $e) {
      $queue->releaseItem($item);
      $queue->deleteQueue();
      watchdog_exception('bridtv', $e);
    }
    catch (\Exception $e) {
      // In case of any other kind of exception, log it and leave the item
      // in the queue to be processed again later.
      watchdog_exception('bridtv', $e);
    }
    $this->logger->error('The Brid.TV queue for video data synchronization has stopped working properly. See the Watchdog log messages for further messages.');
    return FALSE;
  }

  public function queueRemoteListItem($page, $queue_next) {
    $this->queue->createItem(['page' => $page, 'q_next' => $queue_next]);
  }

  public function queueEntityItem($entity_id, $queue_next) {
    $this->queue->createItem(['entity_id' => $entity_id, 'q_next' => $queue_next]);
  }

  public function queueMultipleEntitiesItem(array $entity_ids, $queue_next) {
    $this->queue->createItem(['entity_ids' => $entity_ids, 'q_next' => $queue_next]);
  }

  /**
   * @return \Drupal\bridtv\BridApiConsumer
   */
  public function getConsumer() {
    return $this->consumer;
  }

  /**
   * @return \Drupal\bridtv\BridEntityResolver
   */
  public function getEntityResolver() {
    return $this->entityResolver;
  }

  public function autocreateEnabled($autocreate = NULL) {
    if (isset($autocreate)) {
      $this->autocreateEnabled = !empty($autocreate);
    }
    return $this->autocreateEnabled;
  }

  public function autodeleteEnabled($autodelete = NULL) {
    if (isset($autodelete)) {
      $this->autodeleteEnabled = !empty($autodelete);
    }
    return $this->autodeleteEnabled;
  }

}
