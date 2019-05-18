<?php

namespace Drupal\entity_staging\EventSubscriber;

use Drupal\entity_staging\EntityStagingManager;
use Drupal\entity_staging\Event\EntityStagingBeforeExportEvent;
use Drupal\entity_staging\Event\EntityStagingEvents;
use Drupal\Core\File\FileSystem;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribe to EntityStagingEvents::BEFORE_EXPORT events.
 *
 * Perform action before export file entities.
 */
class EntityStagingExportFileSubscriber implements EventSubscriberInterface {

  /**
   * The content staging manager service.
   *
   * @var \Drupal\entity_staging\EntityStagingManager
   */
  protected $contentStagingManager;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  protected $fileSystem;

  /**
   * EntityStagingExportFileSubscriber constructor.
   *
   * @param \Drupal\entity_staging\EntityStagingManager $entity_staging_manager
   *   The content staging manager service.
   */
  public function __construct(EntityStagingManager $entity_staging_manager, FileSystem $file_system) {
    $this->contentStagingManager = $entity_staging_manager;
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[EntityStagingEvents::BEFORE_EXPORT][] = ['exportFiles', -10];

    return $events;
  }

  /**
   * Export all files.
   *
   * @param \Drupal\entity_staging\Event\EntityStagingBeforeExportEvent $event
   */
  public function exportFiles(EntityStagingBeforeExportEvent $event) {
    if ($event->getEntityTypeId() == 'file') {
      $export_path = realpath(DRUPAL_ROOT . '/' . $this->contentStagingManager->getDirectory());

      /** @var \Drupal\file\Entity\File $file */
      foreach ($event->getEntities()['file'] as $file) {
        $folder = $export_path . '/files/' . dirname(file_uri_target($file->getFileUri()));
        if (!file_exists($folder)) {
          mkdir($folder, 0777, TRUE);
        }
        file_put_contents($folder . '/' . $this->fileSystem->basename($file->getFileUri()), file_get_contents($file->getFileUri()));
      }
    }
  }

}
