<?php

namespace Drupal\scheduled_message\Plugin\QueueWorker;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\message\Entity\Message;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EntityQueue.
 *
 * (Re)schedules pending messages for each entity in the queue.
 *
 * @QueueWorker (
 *   id = "scheduled_message_entity",
 *   title = @Translation("Set up/update scheduled messages for an entity."),
 *   cron = {"time" = 20}
 * )
 */
class EntityQueue extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  protected $entityTypeManager;

  protected $logger;

  /**
   * Constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManager $entityTypeManager, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $this->logger = $logger;
  }

  /**
   * Class factory.
   *
   * @inheritDoc
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('logger.factory')->get('scheduled_message')
    );
  }

  /**
   * Re-calculate all pending scheduled messages for an entity.
   *
   * @inheritDoc
   */
  public function processItem($data) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = isset($data->entity) ?
      $data->entity :
      $this->entityTypeManager
        ->getStorage($data->entityStorageType)->load($data->entity_id);
    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $entityType */
    $entityType = $this->entityTypeManager
      ->getStorage($data->entityType)->load($data->entityTypeId);

    $storage = $this->entityTypeManager->getStorage('message');
    // First get a list of all existing messages that reference this $entity.
    $query = $storage->getQuery();
    // For time comparison:
    $today = new DrupalDateTime();
    $today->setTimezone(new \DateTimeZone(DATETIME_STORAGE_TIMEZONE));
    $today_formatted = $today->format(DATETIME_DATE_STORAGE_FORMAT);

    $message_ids = $query
      ->condition('field_message_related_item', $entity->id())
      ->execute();
    $messages = $storage->loadMultiple($message_ids);

    $found = [];
    foreach ($messages as $message) {
      $found[$message->field_schedule_id->value] = $message;
    }

    // Now get a list of all messages that should exist, with send date.
    /** @var \Drupal\scheduled_message\ScheduledMessagePluginCollection $messages */
    $messages = $entityType->getMessages();
    /** @var \Drupal\scheduled_message\Plugin\ScheduledMessageInterface $message */
    foreach ($messages as $message) {
      $fullfield = $message->getConfiguration()['data']['date_field'];
      $field_parts = explode('.', $fullfield);
      if (count($field_parts) == 2) {
        $field_name = $field_parts[0];
        $subfield = $field_parts[1];
        $date = $entity->$field_name->$subfield;
      }
      else {
        $field_name = $field_parts[0];
        $date = date('Y-m-d', $entity->$field_name->value);
      }
      $offset = $message->getConfiguration()['data']['offset'];
      if (!empty($offset)) {
        $date .= ' ' . $offset;
      }
      $date = new DrupalDateTime($date);
      $date->setTimezone(new \DateTimeZone(DATETIME_STORAGE_TIMEZONE));
      $date_formatted = $date->format(DATETIME_DATE_STORAGE_FORMAT);
      $item = new \stdClass();
      $item->uuid = $message->getUuid();
      $item->send_date = $date_formatted;
      $item->state = explode(',', $message->getConfiguration()['data']['state']);
      $item->message = $message->getConfiguration()['data']['message'];
      $scheduled[$item->uuid] = $item;
    }

    // Now find adds/removes.
    $adds = [];
    foreach ($scheduled as $uuid => $scheduleItem) {
      if (!in_array($uuid, array_keys($found))) {
        if (in_array($entity->get('state')->value, $scheduleItem->state) &&
          $scheduleItem->send_date >= $today_formatted
        ) {
          $adds[] = $scheduleItem;
        }
        // Not an existing message entity.
        continue;
      }
      // Compare with current message:
      // Is date the same?
      // Is the entity in a valid state.
      /** @var \Drupal\message\MessageInterface $message */
      $message = $found[$uuid];
      $messageState = $message->field_send_state->value;
      if (in_array($entity->state->value, $scheduleItem->state)) {
        if ($messageState == 'sent') {
          // Then ignore this message entirely.
          unset($found[$uuid]);
          continue;
        }
        // Updates...
        if ($message->field_send_date->value != $scheduleItem->send_date) {
          $message->field_send_date->value = $scheduleItem->send_date;
          $message->save();
          $this->logger->notice('Message @mid for @uuid re-saved. Send date: @date',
            [
              '@mid' => $message->id(),
              '@uuid' => $uuid,
              '@date' => $scheduleItem->send_date,
            ]);
        }
      }
      else {
        $message->field_send_state = 'canceled';
        $message->save();
        $this->logger->notice('Message @mid for @uuid canceled.',
          ['@mid' => $message->id(), '@uuid' => $uuid]);
      }
      unset($found[$uuid]);
    }

    // $found now contains messages that no longer should exist.
    foreach ($found as $uuid => $message) {
      $this->logger->notice('Deleting @mid message from @uuid.');
      $message->delete();
    }

    // Finally, generate new messages for remaining schedules.
    foreach ($adds as $scheduleItem) {
      $item = [
        'bundle' => $scheduleItem->message,
        'template' => $scheduleItem->message,
        'field_send_date' => $scheduleItem->send_date,
        'field_send_state' => 'pending',
        'field_schedule_id' => $scheduleItem->uuid,
        'field_message_related_item' => $entity,
      ];
      $message = Message::create($item);
      $message->setOwner($entity->getOwner());
      $message->save();
      $this->logger->notice('Created message @mid for @uuid to send on @date.', [
        '@mid' => $message->id(),
        '@uuid' => $scheduleItem->uuid,
        '@date' => $scheduleItem->send_date,
      ]);
    }
  }

}
