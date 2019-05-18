<?php

namespace Drupal\scheduled_executable;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Queue\QueueFactory;

/**
 * TODO: class docs.
 */
class Queuer {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The scheduled executable resolver manager service.
   *
   * @var
   */
  protected $scheduledExecutableResolverManager;

  /**
   * The execution queue.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $executionQueue;

  /**
   * Constructs a new Queuer.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param  $plugin_manager_scheduled_executable_resolver
   *   The plugin manager scheduled executable resolver service.
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   The queue factory
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, $plugin_manager_scheduled_executable_resolver, QueueFactory $queue_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->scheduledExecutableResolverManager = $plugin_manager_scheduled_executable_resolver;

    $this->executionQueue = $queue_factory->get('scheduled_executable_execute');
    $this->scheduledExecutableStorage = $entity_type_manager->getStorage('scheduled_executable');
  }

  /**
   * Handler for hook_cron().
   *
   * Retrieves scheduled_executable entities, resolves them, and queues them
   * for execution.
   */
  public function cron() {
    // Get the SEs to execute.
    // @todo: move this query to a storage handler class.
    $ids = \Drupal::database()->query('SELECT id FROM {scheduled_executable}
      WHERE execution <= :time AND queued IS NULL
      ORDER BY execution, group_name', [
      ':time' => \Drupal::time()->getRequestTime(),
    ])->fetchCol();

    $this->processItems($ids);
  }

  /**
   * Resolves groups of items and queues them.
   *
   * @param array $ids
   *   An array of scheduled_executable entity IDs.
   */
  protected function processItems($ids) {
    $grouped_items = [];

    // Group items by execution time and group name.
    foreach ($this->scheduledExecutableStorage->loadMultiple($ids) as $id => $scheduled_executable) {
      $grouped_items[$scheduled_executable->execution->value][$scheduled_executable->group_name->value][$id] = $scheduled_executable;
    }

    foreach ($grouped_items as $execution_time => $items_for_time) {
      foreach ($items_for_time as $group_name => $items_for_group) {
        // Assume all items in the group have the same resolver, so just pick
        // it from the first one.
        $first_item = reset($items_for_group);
        $resolver_id = $first_item->resolver->value;

        $resolver = $this->scheduledExecutableResolverManager->createInstance($resolver_id);

        $resolved_items = $resolver->resolveScheduledItems($items_for_group);

        $this->queueItems($resolved_items);
      }
    }
  }

  /**
   * Queue an array of scheduled executable items for execution.
   *
   * @param \Drupal\scheduled_executable\Entity\ScheduledExecutable[] $items
   *   The array of scheduled_executable entities. These are placed into the
   *   queue in the same order as this array.
   */
  protected function queueItems($items) {
    // Don't assume that the resolver plugin took care of keeping the IDs as
    // array keys.
    foreach ($items as $scheduled_executable) {
      if ($this->executionQueue->createItem($scheduled_executable->id())) {
        // Mark the SE with the timestamp to avoid queueing it more than once.
        $scheduled_executable->setQueuedTime(\Drupal::time()->getRequestTime());
        $scheduled_executable->save();
      }
    }
  }

}
