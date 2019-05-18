<?php

namespace Drupal\node_revisions_autoclean\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node_revisions_autoclean\Services\RevisionsManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class NotifyQueueWorker.
 *
 * @QueueWorker(
 *  id = "cleanup_revisions_worker",
 *  title = @Translation("Cleanup revisions"),
 *  cron = {"time" = 120}
 * )
 */
class CleanupRevisionsQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {
  use StringTranslationTrait;
  /**
   * Drupal\node_revisions_autoclean\Services\RevisionsManager.
   *
   * @var Drupal\node_revisions_autoclean\Services\RevisionsManager
   */
  protected $revisionsManager;

  /**
   * CleanupRevisionsQueueWorker constructor.
   *
   * @param array $configuration
   *   Configuration.
   * @param string $plugin_id
   *   Pugin id.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param Drupal\node_revisions_autoclean\Services\RevisionsManager $revisionsManager
   *   RevisionsManager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RevisionsManager $revisionsManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->revisionsManager = $revisionsManager;
  }

  /**
   * Creates an instance of the plugin.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container to pull out services used in the plugin.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return static
   *   Returns an instance of this plugin.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('node_revisions_autoclean.revisions_manager')
    );
  }

  /**
   * Works on a single queue item.
   *
   * @param mixed $data
   *   The data that was passed to
   *   \Drupal\Core\Queue\QueueInterface::createItem() when the item was queued.
   *
   * @throws \Drupal\Core\Queue\RequeueException
   *   Processing is not yet finished. This will allow another process to claim
   *   the item immediately.
   * @throws \Exception
   *   A QueueWorker plugin may throw an exception to indicate there was a
   *   problem. The cron process will log the exception, and leave the item in
   *   the queue to be processed again later.
   * @throws \Drupal\Core\Queue\SuspendQueueException
   *   More specifically, a SuspendQueueException should be thrown when a
   *   QueueWorker plugin is aware that the problem will affect all subsequent
   *   workers of its queue. For example, a callback that makes HTTP requests
   *   may find that the remote server is not responding. The cron process will
   *   behave as with a normal Exception, and in addition will not attempt to
   *   process further items from the current item's queue during the current
   *   cron run.
   *
   * @see \Drupal\Core\Cron::processQueues()
   */
  public function processItem($data) {
    $revisions = $this->revisionsManager->revisionsToDelete($data->node);
    \Drupal::logger('node_revisions_autoclean')->info($this->t("Deleting @count old(s) revision(s) for node @nid : @label", [
      '@count' => count($revisions),
      '@nid' => $data->node->id(),
      '@label' => $data->node->label(),
    ]));
    $this->revisionsManager->deleteRevisions($revisions);
  }

}
