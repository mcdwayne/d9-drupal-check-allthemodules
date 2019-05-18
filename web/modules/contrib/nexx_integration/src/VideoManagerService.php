<?php

namespace Drupal\nexx_integration;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Class VideoManagerService.
 *
 * Service for handling nexx video media entities. This is work in progress
 * and started with the refactoring of the videoFieldName method.
 *
 * @package Drupal\nexx_integration
 */
class VideoManagerService implements VideoManagerServiceInterface {
  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * VideoManagerService constructor.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The config factory service.
   */
  public function __construct(
    EntityFieldManagerInterface $entity_field_manager,
    ConfigFactoryInterface $config_factory,
    LoggerChannelFactoryInterface $logger
  ) {
    $this->entityFieldManager = $entity_field_manager;
    $this->config = $config_factory->get('nexx_integration.settings');
    $this->logger = $logger->get('nexx_integration');
  }

  /**
   * {@inheritdoc}
   */
  public function videoFieldName() {
    $fieldDefinitions = $this->entityFieldManager->getFieldDefinitions($this->entityType(), $this->videoBundle());
    foreach ($fieldDefinitions as $fieldName => $fieldDefinition) {
      if ($fieldDefinition->getType() === 'nexx_video_data') {
        $videoField = $fieldName;
        break;
      }
    }

    if (empty($videoField)) {
      throw new \Exception('No video data field defined');
    }

    return $videoField;
  }

  /**
   * {@inheritdoc}
   */
  public function entityType() {
    return 'media';
  }

  /**
   * {@inheritdoc}
   */
  public function videoBundle() {
    if (!$videoBundle = $this->config->get('video_bundle')
    ) {
      throw new \Exception('There is no video bundle setup. Please configure module first.');
    }

    return $videoBundle;
  }

}
