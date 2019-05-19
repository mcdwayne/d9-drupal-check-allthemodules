<?php

namespace Drupal\tome_sync;

use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\file\FileInterface;
use Drupal\tome_base\PathTrait;
use Drupal\tome_sync\Event\ContentCrudEvent;
use Drupal\tome_sync\Event\TomeSyncEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Serializer\Serializer;

/**
 * Handles exporting of content and file entities.
 *
 * @internal
 */
class Exporter implements ExporterInterface {

  use FileTrait;
  use PathTrait;
  use ContentIndexerTrait;

  /**
   * The target content storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $contentStorage;

  /**
   * The serializer.
   *
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected $serializer;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * An array of excluded entity types.
   *
   * @var string[]
   */
  protected static $excludedTypes = [
    'content_moderation_state',
  ];

  /**
   * Creates an Exporter object.
   *
   * @param \Drupal\Core\Config\StorageInterface $content_storage
   *   The target content storage.
   * @param \Symfony\Component\Serializer\Serializer $serializer
   *   The serializer.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(StorageInterface $content_storage, Serializer $serializer, EntityTypeManagerInterface $entity_type_manager, EventDispatcherInterface $event_dispatcher) {
    $this->contentStorage = $content_storage;
    $this->serializer = $serializer;
    $this->entityTypeManager = $entity_type_manager;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function getContentToExport() {
    $entities = [];
    $definitions = array_diff_key($this->entityTypeManager->getDefinitions(), array_flip(self::$excludedTypes));
    foreach ($definitions as $entity_type) {
      if (is_a($entity_type->getClass(), '\Drupal\Core\Entity\ContentEntityInterface', TRUE)) {
        $storage = $this->entityTypeManager->getStorage($entity_type->id());
        $entities[$entity_type->id()] = $storage->getQuery()->execute();
      }
    }
    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteExportDirectories() {
    $this->contentStorage->deleteAll();
    $file_directory = $this->getFileDirectory();
    $this->deleteContentIndex();
    if (file_exists($file_directory)) {
      if (!file_unmanaged_delete_recursive($file_directory)) {
        return FALSE;
      }
    }
    $this->ensureFileDirectory();
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function exportContent(ContentEntityInterface $entity) {
    if (in_array($entity->getEntityTypeId(), self::$excludedTypes, TRUE)) {
      return;
    }
    $data = $this->serializer->normalize($entity, 'json');
    $this->contentStorage->write(TomeSyncHelper::getContentName($entity), $data);
    $this->indexContent($entity);
    if ($entity instanceof FileInterface) {
      $this->exportFile($entity);
    }
    $event = new ContentCrudEvent($entity);
    $this->eventDispatcher->dispatch(TomeSyncEvents::EXPORT_CONTENT, $event);
  }

  /**
   * Exports a file to the export directory.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file entity.
   */
  protected function exportFile(FileInterface $file) {
    $this->ensureFileDirectory();
    $file_directory = $this->getFileDirectory();
    if (strpos($file->getFileUri(), 'public://') === 0 && file_exists($file->getFileUri())) {
      $destination = $this->joinPaths($file_directory, file_uri_target($file->getFileUri()));
      $directory = dirname($destination);
      file_prepare_directory($directory, FILE_CREATE_DIRECTORY);
      file_unmanaged_copy($file->getFileUri(), $destination, FILE_EXISTS_REPLACE);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteContentExport(ContentEntityInterface $entity) {
    // It would be cool if hook_entity_translation_delete() is invoked for
    // every translation of an entity when it's deleted. But it isn't. :-(.
    foreach (array_keys($entity->getTranslationLanguages()) as $langcode) {
      $this->contentStorage->delete(TomeSyncHelper::getContentName($entity->getTranslation($langcode)));
      $this->unIndexContent($entity);
    }
    if ($entity instanceof FileInterface) {
      $this->deleteFileExport($entity);
    }
    $event = new ContentCrudEvent($entity);
    $this->eventDispatcher->dispatch(TomeSyncEvents::DELETE_CONTENT, $event);
  }

  /**
   * Deletes an exported file.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file entity.
   */
  protected function deleteFileExport(FileInterface $file) {
    $file_directory = $this->getFileDirectory();
    if (strpos($file->getFileUri(), 'public://') === 0) {
      $path = $this->joinPaths($file_directory, file_uri_target($file->getFileUri()));
      if (file_exists($path)) {
        file_unmanaged_delete($path);
      }
    }
  }

}
