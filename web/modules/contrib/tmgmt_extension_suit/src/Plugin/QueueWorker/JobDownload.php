<?php

namespace Drupal\tmgmt_extension_suit\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\tmgmt_extension_suit\ExtendedTranslatorPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Executes interface translation queue tasks.
 *
 * @QueueWorker(
 *   id = "tmgmt_extension_suit_download",
 *   title = @Translation("Job translation download"),
 *   cron = {"time" = 30}
 * )
 */
class JobDownload extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The queue object.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $queue;

  /**
   * @var LoggerInterface
   */
  protected $logger;

  /**
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Constructs a new LocaleTranslation object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Queue\QueueInterface $queue
   *   The queue object.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, QueueInterface $queue, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->queue = $queue;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('queue')->get('tmgmt_extension_suit_download', TRUE),
      $container->get('logger.channel.tmgmt_extension_suit')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $id = $data['id'];

    try {
      $job = entity_load('tmgmt_job', $id);

      if (empty($job)) {
        $this->logger->error(t('Downloading translation for a job :job_id is failed: non-existent job. This job has been deleted from admin UI but queue item is still in the queue.', [
          ':job_id' => $id,
        ])->render());
        return;
      }

      $plugin = $job->getTranslator()->getPlugin();
      if ($plugin instanceof ExtendedTranslatorPluginInterface &&
        $plugin->isReadyForDownload($job)
      ) {
        $plugin->downloadTranslation($job);
      }
    }
    catch (\Exception $e) {
      $this->logger->error($e->getMessage());
    }
  }
}
