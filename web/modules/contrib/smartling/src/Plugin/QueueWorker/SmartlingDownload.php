<?php

/**
 * @file
 * Contains \Drupal\smartling\Plugin\QueueWorker\SmartlingDownload.
 */

namespace Drupal\smartling\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\smartling\SourceManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Executes download translation queue task.
 *
 * @QueueWorker(
 *   id = "smartling_download",
 *   title = @Translation("Downloads translation file for submission"),
 *   cron = {"time" = 30}
 * )
 *
 * @todo investigate way to create base class and put constructor and factory
 *   methods there.
 */
class SmartlingDownload extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The source plugin manager.
   *
   * @var \Drupal\smartling\SourceManager
   */
  protected $sourcePluginManager;

  /**
   * Stores source plugins.
   *
   * @var \Drupal\smartling\SourcePluginInterface[]
   */
  protected $sourcePlugins;

  /**
   * The submission storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * Constructs a new SmartlingDownload object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger.
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
   *   The submission storage.
   * @param \Drupal\smartling\SourceManager $source_plugin_manager
   *   The source plugin manager.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, LoggerChannelInterface $logger, EntityStorageInterface $entity_storage, SourceManager $source_plugin_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->logger = $logger;
    $this->entityStorage = $entity_storage;
    $this->sourcePluginManager = $source_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.channel.smartling'),
      $container->get('entity.manager')->getStorage('smartling_submission'),
      $container->get('plugin.manager.smartling.source')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($submission_id) {
    /** @var \Drupal\smartling\SmartlingSubmissionInterface $submission */
    $submission = $this->entityStorage->load($submission_id);
    if (!$submission) {
      $this->logger->error('Submission @id does not exists for download.', [
        '@id' => $submission_id,
      ]);
      return;
    }

    $entity_type_id = $submission->get('entity_type')->value;

    $manager = \Drupal::entityManager();
    if ($manager->hasHandler($entity_type_id, 'smartling')) {
      /** @var \Drupal\smartling\SmartlingEntityHandler $handler */
      $handler = $manager->getHandler($entity_type_id, 'smartling');
      if ($handler->downloadTranslation($submission)) {
        $this->logger->info('Submission downloaded %title', [
          '%title' => $submission->label(),
        ]);
      }
      else {
        $this->logger->error('Submission download failed %title', [
          '%title' => $submission->label(),
        ]);
      }
    }
  }

  /**
   * Singleton wrapper for source plugin instances.
   *
   * @param string $id
   *   Source plugin id.
   * @return \Drupal\smartling\SourcePluginInterface
   *   Source plugin instance.
   */
  protected function getSourcePlugin($id = 'content') {
    // @todo Implement for config entities.
    if (empty($this->sourcePlugins[$id])) {
      $this->sourcePlugins[$id] = $this->sourcePluginManager->createInstance($id);
    }

    return $this->sourcePlugins[$id];
  }

}
