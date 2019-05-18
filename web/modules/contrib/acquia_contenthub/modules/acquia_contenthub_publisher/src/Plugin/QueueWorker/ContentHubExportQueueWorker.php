<?php

namespace Drupal\acquia_contenthub_publisher\Plugin\QueueWorker;

use Drupal\acquia_contenthub\Client\ClientFactory;
use Drupal\acquia_contenthub\ContentHubCommonActions;
use Drupal\acquia_contenthub_publisher\PublisherTracker;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Uuid\Uuid;


/**
 * Acquia ContentHub queue worker.
 *
 * @QueueWorker(
 *   id = "acquia_contenthub_publish_export",
 *   title = "Queue Worker to export entities to contenthub."
 * )
 */
class ContentHubExportQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The common contenthub actions object.
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
   * The published entity tracker.
   *
   * @var \Drupal\acquia_contenthub_publisher\PublisherTracker
   */
  protected $tracker;

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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\acquia_contenthub\ContentHubCommonActions
   *   The common contenthub actions object.
   * @param \Drupal\acquia_contenthub\Client\ClientFactory $factory
   *   The client factory.
   * @param \Drupal\acquia_contenthub_publisher\PublisherTracker $tracker
   *   The published entity tracker.
   *   The event dispatcher.
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
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ContentHubCommonActions $common, ClientFactory $factory, PublisherTracker $tracker, ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger_factory, array $configuration, $plugin_id, $plugin_definition) {
    $this->entityTypeManager = $entity_type_manager;
    $this->common = $common;
    $this->factory = $factory;
    $this->tracker = $tracker;
    $this->configFactory = $config_factory;
    $this->loggerFactory = $logger_factory;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('acquia_contenthub_common_actions'),
      $container->get('acquia_contenthub.client.factory'),
      $container->get('acquia_contenthub_publisher.tracker'),
      $container->get('config.factory'),
      $container->get('logger.factory'),
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $client = $this->factory->getClient();
    $storage = $this->entityTypeManager->getStorage($data->type);

    $entity = $storage->loadByProperties(['uuid' => $data->uuid]);

    // Entity missing, nothing to do here.
    if (!$entity) {
      return TRUE;
    }

    $entity = reset($entity);
    $entities = [];
    $output = $this->common->getEntityCdf($entity, $entities);

    // ContentHub backend determines new or update on the PUT endpoint.
    $response = $client->putEntities(...$output);
    if ($response->getStatusCode() == 202) {
      $entity_uuids = [];
      foreach ($output as $item) {
        $wrapper = !empty($entities[$item->getUuid()]) ? $entities[$item->getUuid()] : NULL;
        if ($wrapper) {
          $this->tracker->track($wrapper->getEntity(), $wrapper->getHash());
        }
        $entity_uuids[] = $item->getUuid();
      }

      $config = $this->configFactory->get('acquia_contenthub.admin_settings');
      $webhook = $config->get('webhook.uuid');
      $logger = $this->loggerFactory->get('acquia_contenthub');

      if (Uuid::isValid($webhook)) {
        try {
          $client->addEntitiesToInterestList($webhook, $entity_uuids);
          $logger->info('Exported entities added to Interest List on Content Hub');
        } catch (\Exception $e) {
          $logger->error(sprintf('Message: %s.', $e->getMessage()));
        }
      } else {
        $logger->warning('Site does not have a valid registered webhook and it is required to add entities to the site\'s interest list in Content Hub.');
      }

      return count($output);
    }

    return FALSE;
  }

}
