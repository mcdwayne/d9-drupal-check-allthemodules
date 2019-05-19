<?php

/**
 * @file
 * Contains \Drupal\smartling\Plugin\smartling\Source\ContentEntitySource.
 */

namespace Drupal\smartling\Plugin\smartling\Source;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\File\FileSystem;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\smartling\ApiWrapper\ApiWrapperInterface;
use Drupal\smartling\SmartlingSubmissionInterface;
use Drupal\smartling\SourcePluginBase;
use Drupal\Core\Render\Element;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\Serializer;
use Drupal\Core\Entity\EntityInterface;

/**
 * Content entity source plugin controller.
 *
 * @SourcePlugin(
 *   id = "configuration",
 *   label = @Translation("Configuration Entity"),
 *   description = @Translation("Source handler for config entities.")
 * )
 */
class ConfigEntitySource extends SourcePluginBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected $serializer;

  /**
   * The smartling API wrapper.
   *
   * @var \Drupal\smartling\ApiWrapper\ApiWrapperInterface
   */
  protected $api;

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * @var \Drupal\Core\File\FileSystem
   */
  protected $fileSystem;

  /**
   * @var \Drupal\smartling\SourcePluginInterface
   */
  protected $sourcePlugin;

  /**
   * Creates TranslateNode action.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   * @param \Symfony\Component\Serializer\Serializer $serializer
   * @param \Drupal\smartling\ApiWrapper\ApiWrapperInterface $smartling_api_wrapper
   *   The smartling API wrapper.
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
   *   SmartlingSubmission entity storage.
   * @param \Drupal\Core\File\FileSystem $file_system
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   Immutable config instance that contains smartling settings.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerChannelInterface $logger, Serializer $serializer, ApiWrapperInterface $smartling_api_wrapper, EntityStorageInterface $entity_storage, FileSystem $file_system, ImmutableConfig $config) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->logger = $logger;
    $this->serializer = $serializer;
    $this->api = $smartling_api_wrapper;
    $this->entityStorage = $entity_storage;
    $this->fileSystem = $file_system;
    $this->config = $config;
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
      $container->get('serializer'),
      $container->get('smartling.api_wrapper'),
      $container->get('entity.manager')->getStorage('smartling_submission'),
      $container->get('file_system'),
      $container->get('config.factory')->get('smartling.settings')
    );
  }

  /**
   * @inheritdoc
   */
  public function getTranslatableXML(EntityInterface $entity) {
    $srv = \Drupal::getContainer()->get('smartling_config_translation.config_translation');
    $str = [$entity->id() => $srv->getSourceData($entity)];
    //$strings[] = $str;
    $encoder = \Drupal::getContainer()->get('serializer.encoder.smartling_config_xml');// 'serializer.encoder.smartling_xml');
    $xml = $encoder->encode($str, 'smartling_xml');

    return $xml;
  }

  /**
   * @inheritdoc
   */
  public function saveTranslation($xml, SmartlingSubmissionInterface $submission) {
    $srv = \Drupal::getContainer()->get('smartling_config_translation.config_translation');
    $encoder = \Drupal::getContainer()->get('serializer.encoder.smartling_config_xml');// 'serializer.encoder.smartling_xml');
    $data = $encoder->decode($xml, 'smartling_xml');


    $srv->saveConfig($submission->get('entity_bundle')->value, $submission->get('entity_id')->value, $submission->get('target_language')->value, $data);
    return TRUE;
  }
}
