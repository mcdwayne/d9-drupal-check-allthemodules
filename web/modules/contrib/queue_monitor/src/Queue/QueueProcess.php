<?php
namespace Drupal\queue_monitor\Queue;

use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueWorkerManagerInterface;

class QueueProcess {
  protected $workerManager;

  protected $queueService;

  protected static $queues;

  /**
   * Constructs a new QueueMonitorConsoleCommand object.
   *
   * @param \Drupal\Core\Queue\QueueWorkerManagerInterface $workerManager
   * @param \Drupal\Core\Queue\QueueFactory                $queueService
   */
  public function __construct(QueueWorkerManagerInterface $workerManager, QueueFactory $queueService) {
    $this->workerManager = $workerManager;
    $this->queueService = $queueService;
  }

  /**
   * get worker manager api.
   *
   * @return \Drupal\Core\Queue\QueueWorkerManagerInterface
   */
  public function getWorkerManager() {
    return $this->workerManager;
  }

  /**
   * @return \Drupal\Core\Queue\QueueFactory
   */
  public function getQueueService() {
    return $this->queueService;
  }

  /**
   * {@inheritdoc}
   */
  public function getQueues() {
    static::$queues;
    if (!isset(static::$queues)) {
      static::$queues = [];
      foreach ($this->getWorkerManager()->getDefinitions() as $name => $info) {
        static::$queues[$name] = $info;
      }
    }
    return static::$queues;
  }

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\Core\Queue\QueueInterface
   */
  public function getQueue($name) {
    return $this->getQueueService()->get($name);
  }

  /**
   * @param $name
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function queueProcess($name) {
    $worker = $this->getWorkerManager()->createInstance($name);
    $queue = $this->getQueue($name);
    while ($item = $queue->claimItem()) {
      try {
        \Drupal::logger('queue_monitor')->info(\t('Processing item @id from @name queue.', [
          '@name' => $name,
          '@id' => $item->item_id,
        ]));
        $worker->processItem($item->data);
        $queue->deleteItem($item);

      } catch (RequeueException $e) {

        // The worker requested the task to be immediately requeued.
        $queue->releaseItem($item);

      } catch (SuspendQueueException $e) {

        // If the worker indicates there is a problem with the whole queue,
        // release the item.
        $queue->releaseItem($item);
        throw new \Exception($e->getMessage());

      }
    }
  }

  public function queueRun($name) {
    $this->queueProcess($name);
  }

  public function queueRunAll() {
    foreach (array_keys($this->getQueues()) as $name) {
      $this->queueProcess($name);
    }
  }
}
