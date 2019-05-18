<?php

namespace Drupal\mob_queue;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Queue\QueueWorkerManagerInterface;

/**
 * Discovery and instantiation of default queue jobs.
 */
class QueueJobOperator {

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The queue service.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * @var \Drupal\Core\Queue\QueueWorkerManagerInterface
   */
  protected $queueManager;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * CronJobDiscovery constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Queue\QueueWorkerManagerInterface $queue_manager
   *   The queue manager.
   */
  public function __construct(ModuleHandlerInterface $module_handler, QueueFactory $queue_factory, QueueWorkerManagerInterface $queue_manager, ConfigFactoryInterface $config_factory) {
    $this->moduleHandler = $module_handler;
    $this->queueFactory = $queue_factory;
    $this->queueManager = $queue_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * Get a list of queue jobs that will use drush command.
   */
  public function getQueueJobs() {
    $queues = $this->queueManager->getDefinitions();
    $exe_queues = [];

    $mob_queues = \Drupal::config('mob_queue.settings')->get('mob_qinfo');
    foreach ($queues as $name => $queue) {
      if (isset($mob_queues[$name]) && $mob_queues[$name]) {
        $exe_queues[$name] = $queue;
      }
    }
    return $exe_queues;
  }

  /**
   * Processes drush queues.
   */
  public function processQueues($queue_name, $info) {
    $this->queueFactory->get($queue_name)->createQueue();

    $queue_worker = $this->queueManager->createInstance($queue_name);
    $end = time() + (isset($info['mob_queue']['time']) ? $info['mob_queue']['time'] : 15);
    $queue = $this->queueFactory->get($queue_name);
    while (time() < $end && ($item = $queue->claimItem())) {
      try {
        $queue_worker->processItem($item->data);
        $queue->deleteItem($item);
      }
      catch (RequeueException $e) {
        // The worker requested the task be immediately requeued.
        $queue->releaseItem($item);
      }
      catch (SuspendQueueException $e) {
        // If the worker indicates there is a problem with the whole queue,
        // release the item and skip to the next queue.
        $queue->releaseItem($item);

        watchdog_exception('mob_queue', $e);

        // Stop processing the current queue.
        return;
      }
      catch (\Exception $e) {
        // In case of any other kind of exception, log it and leave the item
        // in the queue to be processed again later.
        watchdog_exception('mob_queue', $e);
      }
    }
  }

  /**
   * Automatically discovers and creates default queue jobs.
   */
  public function discoverQueueJobs() {
    return array_keys($this->queueManager->getDefinitions());
  }
}

