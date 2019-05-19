<?php

/**
 * @file
 * Contains \Drupal\smartling\SmartlingEntityHandler.
 */

namespace Drupal\smartling;

use Drupal\Core\Entity\EntityInterface;
use \Drupal\Core\Logger\LoggerChannelInterface;
use \Drupal\Core\Entity\EntityHandlerInterface;
use \Drupal\Core\Entity\EntityStorageInterface;
use \Drupal\smartling\SourceManager;
use \Drupal\smartling\ApiWrapper\ApiWrapperInterface;
use \Drupal\smartling\ApiWrapper\SmartlingApiWrapper;
use \Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\File\FileSystem;


class SmartlingEntityHandler implements EntityHandlerInterface {

  /**
   * Entity type.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityType;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * @var \Drupal\smartling\SourceManager
   */
  protected $sourcePluginManager;

  /**
   * @var \Drupal\smartling\SourcePluginInterface
   */
  protected $sourcePlugin;

  /**
   * @var \Drupal\smartling\ApiWrapper\SmartlingApiWrapper
   */
  protected $smartlingApiWrapper;

  /**
   * @var \Drupal\Core\File\FileSystem
   */
  protected $fileSystem;

  /**
   * Initializes an instance of the content translation controller.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   * @param \Drupal\Core\Logger\LoggerChannelInterface
   *   The info array of the given entity type.
   * @param \Drupal\Core\Entity\EntityStorageInterface
   *   The language manager.
   * @param \Drupal\smartling\SourceManager
   *   The content translation manager service.
   * @param \Drupal\smartling\ApiWrapper\SmartlingApiWrapper
   *   The entity manager.
   * @param \Drupal\Core\File\FileSystem $file_system
   */
  public function __construct(EntityTypeInterface $entity_type, LoggerChannelInterface $logger, EntityStorageInterface $entity_storage, SourceManager $source_plugin_manager, SmartlingApiWrapper $smartling_api_wrapper, FileSystem $file_system) {
    $this->entityType = $entity_type;
    $this->entityStorage = $entity_storage;
    $this->smartlingApiWrapper = $smartling_api_wrapper;
    //sourcePlugins
    $this->sourcePluginManager = $source_plugin_manager;
    $this->logger = $logger;
    $this->fileSystem = $file_system;
  }

  /**
   * Instantiates a new instance of this entity handler.
   *
   * This is a factory method that returns a new instance of this object. The
   * factory should pass any needed dependencies into the constructor of this
   * object, but not the container itself. Every call to this method must return
   * a new instance of this object; that is, it may not implement a singleton.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container this object should use.
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   *
   * @return static
   *   A new instance of the entity handler.
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('logger.channel.smartling'),
      $container->get('entity.manager')->getStorage('smartling_submission'),
      $container->get('plugin.manager.smartling.source'),
      $container->get('smartling.api_wrapper'),
      $container->get('file_system')
    );
  }

  /**
   * Singleton wrapper for source plugin instances.
   *
   * @return \Drupal\smartling\SourcePluginInterface
   *   Source plugin instance.
   *
   * @throws \Exception
   */
  protected function getSourcePlugin() {
    //ContentEntitySource

    if ($this->entityType->isSubclassOf('\Drupal\Core\Entity\ContentEntityInterface')) {
      $entity_meta_type = 'content';
    }
    elseif ($this->entityType->isSubclassOf('\Drupal\Core\Config\Entity\ConfigEntityInterface')) {
      $entity_meta_type = 'configuration';
    }
    else {
      throw new \Exception('Unknown entity meta type (neither "content" nor "configuration")');
    }

    if (empty($this->sourcePlugin)) {
      $this->sourcePlugin = $this->sourcePluginManager->createInstance($entity_meta_type);
    }

    return $this->sourcePlugin;
  }

  /**
   * Method that sends content to be translated to specific languages.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to upload.
   * @param $file_name
   *   The file name to upload.
   * @param $locales
   *   Array of locales to upload.
   *
   * @return bool
   *   TRUE on upload success.
   *
   * @throws \Exception
   */
  public function uploadTranslation(EntityInterface $entity, $file_name, $locales) {
    /** @var \Drupal\smartling\Plugin\smartling\Source\ContentEntitySource $plugin */
    $plugin = $this->getSourcePlugin();
    //$event = $plugin->uploadSubmissions($entity, $file_name, $locales);
    $xml = $plugin->getTranslatableXML($entity);

    $dir = 'public://smartling/content';
    $success = file_prepare_directory($dir, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);
    $success = $success && file_save_htaccess($dir);
    if (!$success) {
      return;
    }

    $file_path = file_unmanaged_save_data($xml, $dir . '/' . $file_name, FILE_EXISTS_REPLACE);
    if (!$file_path) {
      return;
    }

    $event = $this->smartlingApiWrapper->uploadFile(
      $this->fileSystem->realpath($file_path),
      $file_name,
      ApiWrapperInterface::TYPE_XML, $locales
    );


    if (!$event) {
      $this->logger->error('The @file failed to upload', [
        '%file' => $file_name,
      ]);
      return FALSE;
    }
    // Update each submission with returned status.
    /** @var \Drupal\smartling\SmartlingSubmissionInterface[] $submissions */
    $submissions = $this->entityStorage->loadByProperties([
      'entity_type' => $entity->getEntityTypeId(),
      'entity_id' => $entity->id(),
    ]);
    foreach ($submissions as $submission) {
      $language = $submission->get('target_language')->value;
      if (in_array($language, $locales)) {
        $submission
          ->setStatusByEvent($event)
          ->save();
      }
    }
    return TRUE;
  }

  /**
   * Method that downloads content from smartling.
   *
   * @param \Drupal\smartling\SmartlingSubmissionInterface $submission
   *   The Submission.
   *
   * @return bool
   *   The status of download operation.
   */
  public function downloadTranslation(SmartlingSubmissionInterface $submission) {

    $result = $this->smartlingApiWrapper->downloadFile($submission);
    if (!$result) {
      return FALSE;
    }
    /** @var \Drupal\smartling\Plugin\smartling\Source\ContentEntitySource $plugin */
    $plugin = $this->getSourcePlugin();

    $res = $plugin->saveTranslation($result, $submission);

    if ($res) {
      // @todo Save file name and properly update submission.
      $submission
        ->set('progress', 100)
        ->save();
    }
    return $res;
  }

  /**
   * Method that checks status for smartling submissions.
   *
   * @param array $submission_ids
   *   IDs of submission objects.
   *
   * @return array
   *   Keyed array of statuses by submission ID.
   */
  public function checkStatus(array $submission_ids) {
    /* @var \Drupal\smartling\SmartlingSubmissionInterface[] $submissions */
    $submissions = $this->entityStorage->loadMultiple($submission_ids);

    $result = [];
    foreach ($submissions as $submission) {
      $result[$submission->id()] = $this->smartlingApiWrapper->getStatus($submission);
    }

    return $result;
  }
}