<?php

namespace Drupal\xero_contact_sync;

use Drupal\advancedqueue\Entity\Queue;
use Drupal\advancedqueue\Job;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Queue\QueueInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Reacts to entity operations.
 *
 * @see \xero_contact_sync_user_entity_insert().
 *
 * @package Drupal\xero_contact_sync
 */
class XeroContactSyncEntityOperations implements ContainerInjectionInterface {

  /**
   * The user creation queue.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $queue;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new XeroContactSyncEntityOperations object.
   *
   * @param \Drupal\Core\Queue\QueueInterface $queue
   *   The user creation queue.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(QueueInterface $queue, ModuleHandlerInterface $module_handler) {
    $this->queue = $queue;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('queue')->get('xero_contact_sync_create'),
      $container->get('module_handler')
    );
  }

  public function insert(UserInterface $user) {
    // If we already have a contact id, don't queue anything.
    if ($user->get('xero_contact_id')->value !== NULL) {
      return;
    }
    // Queue item for creation.
    if ($this->moduleHandler->moduleExists('advancedqueue')) {
      if (($queue = Queue::load('xero_contact_sync')) == NULL) {
        // Add new queue.
        $data = [
          'id' => 'xero_contact_sync',
          'label' => 'Xero Contact Sync',
          'backend' => 'database',
          'backend_configuration' => ['lease_time' => 60],
          'processor' => 'cron',
          'processing_time' => 60,
          'locked' => TRUE,
        ];
        $queue = Queue::create($data);
        $queue->save();
      }
      $job = Job::create('xero_contact_sync', [
        'user_id' => $user->id(),
      ]);
      $queue->enqueueJob($job);
    }
    else {
      $this->queue->createItem([
        'user_id' => $user->id(),
      ]);
    }
  }

}
