<?php

namespace Drupal\video_sitemap\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\video_sitemap\VideoSitemapGenerator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Executes process nodes with Media Video queue tasks.
 *
 * @QueueWorker(
 *   id = "node_with_video_queue",
 *   title = @Translation("Adds nodes with video with video sitemap location tag"),
 *   cron = {"time" = 60}
 * )
 */
class NodeWithVideoQueue extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The Video Sitemap generator service.
   *
   * @var \Drupal\video_sitemap\VideoSitemapGenerator
   */
  protected $generator;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new NodeWithVideoQueue object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\video_sitemap\VideoSitemapGenerator $video_sitemap_generator
   *   The Video Sitemap generator service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, VideoSitemapGenerator $video_sitemap_generator, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->generator = $video_sitemap_generator;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('video_sitemap.generator'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $storage = $this->entityTypeManager->getStorage($data['entity_type']);
    $entity = $storage->load($data['entity_id']);
    // Entity could be deleted after it was added to the queue
    // so we need to check if it still exists.
    if ($entity) {
      $this->generator->processVideoUsageOnNode($entity);
    }
  }

}
