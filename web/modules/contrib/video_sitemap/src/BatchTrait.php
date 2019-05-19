<?php

namespace Drupal\video_sitemap;

use Drupal\Core\Queue\RequeueException;
use Drupal\Core\Queue\SuspendQueueException;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Video sitemap batch operations trait.
 *
 * @package Drupal\video_sitemap
 */
trait BatchTrait {

  use StringTranslationTrait;

  /**
   * Batch operations.
   *
   * @var array
   */
  protected $batch;

  /**
   * Batch operations definition.
   *
   * @param string $from
   *   Batch trigger source.
   *
   * @return bool
   *   Batch operations result.
   */
  public function batchGenerateSitemap($from = 'form') {
    $this->batch = [
      'title' => $this->t('Generating Video sitemap'),
      'init_message' => $this->t('Initializing...'),
      'progress_message' => $this->t('Generating video sitemap links.'),
      'operations' => [
        [__CLASS__ . '::processQueue', []],
        [__CLASS__ . '::generateSitemapFromIndex', []],
      ],
      'finished' => [__CLASS__, 'finishGeneration'],
    ];

    switch ($from) {
      case 'form':
        batch_set($this->batch);
        return TRUE;
    }
    return FALSE;
  }

  /**
   * Batch process index queue callback.
   *
   * @param array $context
   *   The batch context.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public static function processQueue(array &$context) {
    /** @var \Drupal\Core\Queue\QueueWorkerManagerInterface $queue_manager */
    $queue_manager = \Drupal::service('plugin.manager.queue_worker');
    /** @var \Drupal\Core\Queue\QueueFactory $queue_factory */
    $queue_factory = \Drupal::service('queue');
    $queue_name = 'node_with_video_queue';

    $queue_factory->get($queue_name)->createQueue();
    $queue_worker = $queue_manager->createInstance($queue_name);
    $queue = $queue_factory->get($queue_name);

    $context['finished'] = 0;
    $title = t('Indexing nodes with video');

    try {
      if ($item = $queue->claimItem()) {
        $context['message'] = $title;
        $queue_worker->processItem($item->data);
        $queue->deleteItem($item);
        $context['results']['processed'][] = $item->item_id;
      }
      else {
        $context['finished'] = 1;
      }
    }
    catch (RequeueException $e) {
      // The worker requested the task be immediately requeued.
      $queue->releaseItem($item);
    }
    catch (SuspendQueueException $e) {
      // Release the item if the worker indicates there is a problem with the whole queue.
      $queue->releaseItem($item);

      watchdog_exception('video_sitemap', $e);
      $context['results']['errors'][] = $e->getMessage();

      // Marking the batch job as finished will stop further processing.
      $context['finished'] = 1;
    }
    catch (\Exception $e) {
      watchdog_exception('queue_ui', $e);
      $context['results']['errors'][] = $e->getMessage();
    }
  }

  /**
   * Batch process sitem map generate callback.
   *
   * @param array $context
   *   The batch context.
   */
  public static function generateSitemapFromIndex(array &$context) {
    /** @var \Drupal\video_sitemap\VideoSitemapGenerator $generator */
    $generator = \Drupal::service('video_sitemap.generator');
    $generator->writeSitemap();
  }

  /**
   * Callback function called by the batch API when all operations are finished.
   *
   * @param bool $success
   *   Batch result.
   * @param array $results
   *   Result data.
   * @param array $operations
   *   Batch operations.
   *
   * @return bool
   *   Batch result.
   */
  public static function finishGeneration($success, array $results, array $operations) {
    if ($success) {
      \Drupal::logger('video_sitemap')
        ->info('The video sitemap has been regenerated.');
    }
    else {
      \Drupal::logger('video_sitemap')
        ->error('The generation failed to finish.');
    }

    return $success;
  }

}
