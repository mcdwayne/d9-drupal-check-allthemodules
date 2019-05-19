<?php

namespace Drupal\tmgmt_smartling\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\tmgmt_smartling\Context\ContextUploader;
use Psr\Log\LoggerInterface;
use Drupal\Core\Config\ConfigFactory;

/**
 * Executes interface translation queue tasks.
 *
 * @QueueWorker(
 *   id = "smartling_context_upload",
 *   title = @Translation("Upload context"),
 *   cron = {"time" = 30}
 * )
 */
class ContextUpload extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $contextUploader;

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
   * @param \Drupal\tmgmt_smartling\Context\ContextUploader $context_uploader
   *   The module handler.
   * @param \Drupal\Core\Queue\QueueInterface $queue
   *   The queue object.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    array $plugin_definition,
    ContextUploader $context_uploader,
    QueueInterface $queue,
    LoggerInterface $logger,
    ConfigFactory $config_factory
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->contextUploader = $context_uploader;
    $this->queue = $queue;
    $this->logger = $logger;
    $this->config = $config_factory->get('tmgmt.translator.smartling');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('tmgmt_smartling.utils.context.uploader'),
      $container->get('queue')->get('smartling_context_upload', TRUE),
      $container->get('logger.channel.smartling'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    if (!$data['job_id']) {
      return;
    }

    $job_id = $data['job_id'];
    $url = $data['url'];
    $filename = $data['filename'];
    $job = \Drupal::entityTypeManager()->getStorage('tmgmt_job')
      ->load($job_id);


    //$date = $data['upload_date'];

    if ($job && $job->hasTranslator()) {
      $settings = $job->getTranslator()->getSettings();
    } else {
      $this->logger->warning("Job with ID=@id has no translator plugin.", ['@id' => $job_id]);
      return;
    }

    // Method 'isReadyAcceptContext' will return FALSE in case there is no
    // some/all required credentials. In this case we will re-add item into the
    // queue. We have to prevent this because we need to re-add item only in
    // case when Smartling is still processing uploaded file and isn't ready to
    // accept the context. This is the corner case which is rarely reproducable.
    if (empty($settings['user_id']) || empty($settings['project_id']) || empty($settings['token_secret'])) {
      $this->logger->warning('Skipping uploading context for the job with id = @id because credentials are not correct.', [
        '@id' => $job_id,
      ]);
      return;
    }

    if (!$this->contextUploader->isReadyAcceptContext($filename, $settings)) {
      $data['counter'] = (isset($data['counter'])) ? $data['counter'] + 1 : 1;

      $this->queue->createItem($data);
      return;
    }


    try {
      $this->contextUploader->upload($url, $filename, $settings);
    } catch (\Exception $e) {
      $this->logger->error($e->getMessage());
      return [];
    }
  }
}
