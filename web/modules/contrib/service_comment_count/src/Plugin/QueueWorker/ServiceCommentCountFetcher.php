<?php

namespace Drupal\service_comment_count\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\service_comment_count\CommentServiceManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Processes tasks for example module.
 *
 * @QueueWorker(
 *   id = "service_comment_count_fetcher",
 *   title = @Translation("Service Comment Count: Fetch queue worker"),
 *   cron = {"time" = 90}
 * )
 */
class ServiceCommentCountFetcher extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The comment service manager.
   *
   * @var \Drupal\service_comment_count\CommentServiceManager
   */
  protected $commentServiceManager;

  /**
   * Creates a new ServiceCommentCountFetcher object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\service_comment_count\CommentServiceManager $commentServiceManager
   *   The comment service manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, CommentServiceManager $commentServiceManager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->commentServiceManager = $commentServiceManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.service_comment_count.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($item) {
    $plugin_id = $item['plugin_id'];
    $nids = $item['nids'];

    // Load in the service.
    $service = $this->commentServiceManager->createInstance($plugin_id);

    // Load the node objects for the given list.
    $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);

    // Split the items into the maximum chunks we can fetch with one call.
    $items_per_batch = $service->getListLimit();
    $chunks = array_chunk($nodes, $items_per_batch);

    // Iterate over the chunks and fetches the comments.
    foreach ($chunks as $chunk) {
      $service->fetchCommentCountMultiple($chunk);
    }
  }

}
