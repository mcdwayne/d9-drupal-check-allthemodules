<?php

namespace Drupal\scheduled_message;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Queue\QueueFactory;

/**
 * Class QueueManager.
 *
 * @package Drupal\scheduled_message
 */
class QueueManager {

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The QueueFactory service.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * Constructor.
   */
  public function __construct(EntityTypeManager $entity_type_manager, QueueFactory $queueFactory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->queueFactory = $queueFactory;
  }

  /**
   * Find messages that are scheduled to send now, and add to send queue.
   */
  public function queueMessages() {
    $storage = $this->entityTypeManager->getStorage('message');
    $query = $storage->getQuery();

    /** @var \Drupal\Core\Datetime\DrupalDateTime $today */
    $today = new DrupalDateTime();
    $today->setTimezone(new \DateTimeZone(DATETIME_STORAGE_TIMEZONE));
    $today_date = $today->format(DATETIME_DATE_STORAGE_FORMAT);

    /** @var \Drupal\Core\Queue\QueueInterface $queue */
    $queue = $this->queueFactory->get('cron_scheduled_message');

    $message_ids = $query->condition('field_send_state.value', 'pending')
      ->condition('field_send_date.value', $today_date, '<=')
      ->execute();
    foreach ($message_ids as $message_id) {
      $item = new \stdClass();
      $item->id = $message_id;
      $queue->createItem($item);

      /** @var \Drupal\message\MessageInterface $message */
      $message = $storage->load($message_id);
      $message->field_send_state = 'queued';
      $message->save();
    }
  }

  /**
   * Queue an individual entity for scheduling.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to schedule/reschedule.
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $entityType
   *   The entitytype's config entity.
   */
  public function queueEntity(ContentEntityInterface $entity, ConfigEntityInterface $entityType) {
    $queue = $this->queueFactory->get('scheduled_message_entity');
    $item = new \stdClass();
    $item->entity_id = $entity->id();
    $item->entityStorageType = $entity->getEntityTypeId();
    $item->entityType = $entityType->getEntityTypeId();
    $item->entityTypeId = $entityType->id();
    $queue->createItem($item);
  }

  /**
   * Add entities to the queue.
   *
   * @param array $entities
   *   The entities to add.
   * @param string $config_type
   *   The entity bundle.
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $entityType
   *   The entity type config entity.
   */
  public function queueEntities(array $entities, $config_type, ConfigEntityInterface $entityType) {
    $queue = $this->queueFactory->get('scheduled_message_entity');
    foreach ($entities as $entity) {
      $item = new \stdClass();
      $item->entity_id = $entity->id();
      $item->entityStorageType = $config_type;
      $item->entityType = $entityType->getEntityTypeId();
      $item->entityTypeId = $entityType->id();
      $queue->createItem($item);
    }

  }

  /**
   * Generate all messages listed on Type, according to plugin settings.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Content entity to generate scheduled messages.
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $type
   *   Entity Type to find schedule.
   */
  public function generateScheduledMessages(ContentEntityInterface $entity, ConfigEntityInterface $type) {

  }

}
