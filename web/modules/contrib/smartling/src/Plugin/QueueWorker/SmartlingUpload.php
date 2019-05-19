<?php

/**
 * @file
 * Contains \Drupal\smartling\Plugin\QueueWorker\SmartlingUpload.
 */

namespace Drupal\smartling\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Executes uploading queue task.
 *
 * @QueueWorker(
 *   id = "smartling_upload",
 *   title = @Translation("Uploads file to Smartling"),
 *   cron = {"time" = 30}
 * )
 */
class SmartlingUpload extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a new LocaleTranslation object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The module handler.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, LoggerChannelInterface $logger, EntityManagerInterface $entity_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->logger = $logger;
    $this->entityManager = $entity_manager;
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
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $entity_type_id = $data['entity_type'];
    $entity_id = $data['entity_id'];
    $entity = $this->entityManager
      ->getStorage($entity_type_id)
      ->load($entity_id);
    if (!$entity) {
      $this->logger->error('Entity @type:@id does not exists for upload.', [
        '@type' => $entity_type_id,
        '@id' => $entity_id,
      ]);
      return;
    }

    if ($this->entityManager->hasHandler($entity_type_id, 'smartling')) {
      /** @var \Drupal\smartling\SmartlingEntityHandler $handler */
      $handler = $this->entityManager->getHandler($entity_type_id, 'smartling');
      $handler->uploadTranslation($entity, $data['file_name'], $data['locales']);
    }
  }

}
