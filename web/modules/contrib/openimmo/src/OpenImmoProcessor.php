<?php

namespace Drupal\openimmo;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;

/**
 * Process query openimmo information.
 */
class OpenImmoProcessor implements OpenImmoProcessorInterface {

  /**
   * The openimmo settings.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $openimmoSettings;

  /**
   * The OpenImmoFetcher service.
   *
   * @var \Drupal\openimmo\OpenImmoFetcherInterface
   */
  protected $openimmoFetcher;

  /**
   * The openimmo fetch queue.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $fetchQueue;

  /**
   * Array of release history URLs that we have failed to fetch.
   *
   * @var array
   */
  protected $failed;

  /**
   * The OpenImmoFetcher service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $stateStore;

  /**
   * Constructs a OpenImmoProcessor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   The queue factory.
   * @param \Drupal\openimmo\OpenImmoFetcherInterface $openimmo_fetcher
   *   The openimmo fetcher service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Entity Type Manager service.
   * @param \Drupal\Core\State\StateInterface $state_store
   *   The State service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, QueueFactory $queue_factory, OpenImmoFetcherInterface $openimmo_fetcher, EntityTypeManagerInterface $entity_type_manager, StateInterface $state_store) {
    $this->openimmoFetcher = $openimmo_fetcher;
    $this->openimmoSettings = $config_factory->get('openimmo.settings');
    $this->fetchQueue = $queue_factory->get('openimmo_fetch_tasks');
    $this->entityTypeManager = $entity_type_manager;
    $this->stateStore = $state_store;
  }

  /**
   * {@inheritdoc}
   */
  public function createFetchTask(array $source) {

    $this->fetchQueue->createItem($source);

  }

  /**
   * {@inheritdoc}
   */
  public function fetchData() {
    $end = time() + $this->openimmoSettings->get('fetch.timeout');
    while (time() < $end && $item = $this->fetchQueue->claimItem()) {
      $this->processFetchTask($item->data);
      $this->fetchQueue->deleteItem($item);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function processFetchTask(array $source) {

    $success = FALSE;

    $data = $this->openimmoFetcher->fetchOpenImmoData($source);

    if (!empty($data['data'])) {

      $this->writeToEntityFields($data);

      $success = TRUE;
    }

    $this->stateStore->set('openimmo.last_fetch_time', REQUEST_TIME);

    return $success;
  }

  /**
   * Write fetched data to appropriate entity's fields.
   *
   * @param array $data
   *   Prepared fetched data.
   *
   * @return bool
   *   TRUE if operation finished successfully.
   */
  protected function writeToEntityFields(array $data) {

    // todo: do it in a separate queue.
    $entity_variable_array = explode(':', $data['entity']);
    $entity_id = $entity_variable_array[0];
    $entity_type = $entity_variable_array[1];

    $key_variable_array = explode(':', $data['key_field']);
    $key_field_openimmo = $key_variable_array[0];
    $key_field = $key_variable_array[1];

    $select_mapping = preg_replace('/\r\n|\r|\n/', '', $data['select']);
    $select_mapping = preg_replace('/\s+/', '', $select_mapping);

    foreach ($data['data'] as $row) {

      // todo: add update data or ignore.
      $storage = $this->entityTypeManager->getStorage($entity_id);
      $entity = $storage->create([
        'type' => $entity_type,
        'title' => 'OpenImmo data ' . $row[$key_field_openimmo],
      ]);

      if ($entity->hasField($key_field)) {
        $entity->set($key_field, $row[$key_field_openimmo]);
      }

      $field_variable_array = explode(',', $select_mapping);
      foreach ($field_variable_array as $field_variable) {
        $field_array = explode(':', $field_variable);

        // todo: get field type and use appropriate construction.
        if ($entity->hasField($field_array[1])) {
          $entity->set($field_array[1], $row[$field_array[0]]);
        }
      }

      $entity->save();

    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function numberOfQueueItems() {
    return $this->fetchQueue->numberOfItems();
  }

  /**
   * {@inheritdoc}
   */
  public function claimQueueItem() {
    return $this->fetchQueue->claimItem();
  }

  /**
   * {@inheritdoc}
   */
  public function deleteQueueItem(\stdClass $item) {
    return $this->fetchQueue->deleteItem($item);
  }

}
