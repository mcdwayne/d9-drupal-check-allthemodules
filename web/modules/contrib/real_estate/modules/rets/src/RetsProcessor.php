<?php

namespace Drupal\real_estate_rets;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Process query rets information.
 */
class RetsProcessor implements RetsProcessorInterface {

  /**
   * The rets settings.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $retsSettings;

  /**
   * The RetsFetcher service.
   *
   * @var \Drupal\real_estate_rets\RetsFetcherInterface
   */
  protected $retsFetcher;

  /**
   * The rets fetch queue.
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
   * The RetsFetcher service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a RetsProcessor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   The queue factory.
   * @param \Drupal\real_estate_rets\RetsFetcherInterface $rets_fetcher
   *   The rets fetcher service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Entity Type Manager service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, QueueFactory $queue_factory, RetsFetcherInterface $rets_fetcher, EntityTypeManagerInterface $entity_type_manager) {
    $this->retsFetcher = $rets_fetcher;
    $this->retsSettings = $config_factory->get('rets.settings');
    $this->fetchQueue = $queue_factory->get('real_estate_rets_fetch_tasks');
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function createFetchTask(array $query) {

    $this->fetchQueue->createItem($query);

  }

  /**
   * {@inheritdoc}
   */
  public function fetchData() {

    while ($item = $this->fetchQueue->claimItem()) {
      $this->processFetchTask($item->data);
      $this->fetchQueue->deleteItem($item);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function processFetchTask(array $query) {

    $success = FALSE;

    $data = $this->retsFetcher->fetchRetsData($query);

    if (!empty($data['data'])) {

      $this->writeToEntityFields($data);

      $success = TRUE;
    }

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
    $key_field_rets = $key_variable_array[0];
    $key_field = $key_variable_array[1];

    $select_mapping = preg_replace('/\r\n|\r|\n/', '', $data['select']);
    $select_mapping = preg_replace('/\s+/', '', $select_mapping);

    foreach ($data['data'] as $row) {

      // todo: add update data or ignore.
      $storage = $this->entityTypeManager->getStorage($entity_id);
      $entity = $storage->create([
        'type' => $entity_type,
        'title' => 'RETS data ' . $row[$key_field_rets],
      ]);

      if ($entity->hasField($key_field)) {
        $entity->set($key_field, $row[$key_field_rets]);
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
