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
 *   id = "content",
 *   label = @Translation("Content Entity"),
 *   description = @Translation("Source handler for entities.")
 * )
 */
class ContentEntitySource extends SourcePluginBase implements ContainerFactoryPluginInterface {

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
    return $this->serializer->serialize($entity, 'smartling_xml');
  }

  /**
   * @inheritdoc
   */
  public function saveTranslation($xml, SmartlingSubmissionInterface $submission) {
    // Apply translation to entity fields.
    $entity = $submission->getRelatedEntity();
    $entity_type = $entity->getEntityType();
    $context = ['entity_type' => $entity->getEntityTypeId()];
    // Provide bundle value to properly create entity.
    if ($entity_type->hasKey('bundle')) {
      $context['bundle_key'] = $entity_type->getKey('bundle');
      $context['bundle_value'] = $entity->bundle();
    }
    $entity_class = $entity_type->getClass();
    /** @var \Drupal\Core\Entity\ContentEntityInterface $new_entity */
    $new_entity = $this->serializer->deserialize($xml, $entity_class, 'smartling_xml', $context);

    $lang_code = $submission->get('target_language')->value;
    // Switch entity to current language.
    if ($entity->hasTranslation($lang_code)) {
      $translation = $entity->getTranslation($lang_code);
    }
    else {
      $translation = $entity->addTranslation($lang_code);
    }
    foreach ($new_entity->_restSubmittedFields as $field) {
      if (isset($context['bundle_key']) && $field === $context['bundle_key']) {
        // Skip changing bundle value.
        continue;
      }
      $field_items = $translation->get($field);
      if ($field_items->getFieldDefinition()->isTranslatable()) {
        // Update only translatable fields.
        $field_items->setValue($new_entity->get($field)->getValue());
      }
    }
    $translation->save();
    return TRUE;
  }

}
