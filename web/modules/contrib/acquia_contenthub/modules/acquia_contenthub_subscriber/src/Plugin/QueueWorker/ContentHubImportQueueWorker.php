<?php

namespace Drupal\acquia_contenthub_subscriber\Plugin\QueueWorker;

use Drupal\acquia_contenthub\Client\ClientFactory;
use Drupal\acquia_contenthub\ContentHubCommonActions;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ContentHubImportQueueWorker.
 *
 * @QueueWorker(
 *   id = "acquia_contenthub_subscriber_import",
 *   title = "Queue Worker to import entities from contenthub."
 * )
 */
class ContentHubImportQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The common actions object.
   *
   * @var \Drupal\acquia_contenthub\ContentHubCommonActions
   */
  protected $common;

  /**
   * The client factory.
   *
   * @var \Drupal\acquia_contenthub\Client\ClientFactory
   */
  protected $factory;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The logger channel factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * ContentHubExportQueueWorker constructor.
   *
   * @param \Drupal\acquia_contenthub\ContentHubCommonActions $common
   *   The common actions object.
   * @param \Drupal\acquia_contenthub\Client\ClientFactory $factory
   *   The client factory.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   */
  public function __construct(ContentHubCommonActions $common, ClientFactory $factory, ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger_factory, array $configuration, $plugin_id, $plugin_definition) {
    $this->common = $common;
    $this->factory = $factory;
    $this->configFactory = $config_factory;
    $this->loggerFactory = $logger_factory;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('acquia_contenthub_common_actions'),
      $container->get('acquia_contenthub.client.factory'),
      $container->get('config.factory'),
      $container->get('logger.factory'),
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * Processes acquia_contenthub_subscriber_import queue items.
   *
   * @param mixed $data
   *   The data in the queue.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function processItem($data) {
    $stack = $this->common->importEntities(...explode(', ', $data->uuids));

    $config = $this->configFactory->get('acquia_contenthub.admin_settings');
    $webhook = $config->get('webhook.uuid');

    if ($webhook) {
      try {
        $this
          ->factory
          ->getClient()
          ->addEntitiesToInterestList($webhook, array_keys($stack->getDependencies()));

        $this
          ->loggerFactory
          ->get('acquia_contenthub')
          ->info('Imported entities added to Interest List on Plexus');
      }
      catch (\Exception $e) {
        $this
          ->loggerFactory
          ->get('acquia_contenthub')
          ->error(sprintf('Message: %s.', $e->getMessage()));
      }
    }
  }

}
