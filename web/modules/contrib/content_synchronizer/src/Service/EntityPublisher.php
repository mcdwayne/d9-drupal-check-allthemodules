<?php

namespace Drupal\content_synchronizer\Service;

use Drupal\content_synchronizer\Processors\Entity\EntityProcessorBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\content_synchronizer\Processors\ImportProcessor;

/**
 * THe entity publsher service.
 */
class EntityPublisher {

  const SERVICE_NAME = 'content_synchronizer.entity_publisher';

  /**
   * Save the entity after import.
   *
   * If the entity is revisionable, it creates a new revision.
   * If the entity is new and is a root entity, then it is unpublished.
   */
  public function saveEntity(EntityInterface $entity, $gid = NULL, $existingEntity = NULL, array $dataToImport = []) {

    // Alter entity before import.
    $entityDataToImport = array_key_exists('translations', $dataToImport) ? $dataToImport['translations'][$entity->language()
      ->getId()] : $dataToImport;
    \Drupal::moduleHandler()
      ->alter(EntityProcessorBase::IMPORT_HOOK, $entity, $existingEntity, $entityDataToImport);

    // Try to create a new revision of the current entity.
    $this->saveEntityWithRevision($entity, $gid, $existingEntity);
  }

  /**
   * Try to create a revision of the entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to save.
   * @param string $gid
   *   THe gid.
   * @param \Drupal\Core\Entity\EntityInterface $existingEntity
   *   The existing entity before changes.
   */
  protected function saveEntityWithRevision(EntityInterface $entity, $gid, EntityInterface $existingEntity = NULL) {

    if ($entity->id()) {
      $entityMethods = get_class_methods($entity);
      if (in_array('setRevisionCreationTime', $entityMethods) && in_array('setNewRevision', $entityMethods)) {
        try {
          $revision = clone($entity);
          $revision->setNewRevision(TRUE);
          $revision->revision_log = '[Content Synchronizer] ' . ImportProcessor::getCurrentImportProcessor()
            ->getImport()
            ->label();
          $revision->setRevisionCreationTime(time());

          if ($this->haveToSave($revision, $existingEntity)) {
            $revision->save();
          }
        }
        catch (\Exception $e) {
          $this->saveEntityWithUnpublishedStatus($entity, $gid, $existingEntity);
        }
      }
      else {
        $this->saveEntityWithUnpublishedStatus($entity, $gid, $existingEntity);
      }
    }
    else {
      $this->saveEntityWithUnpublishedStatus($entity, $gid, $existingEntity);
    }
  }

  /**
   * Try to unpublish entity if it needs to be created. Either default save.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to save.
   * @param string $gid
   *   THe gid.
   * @param \Drupal\Core\Entity\EntityInterface $existingEntity
   *   The existing entity before changes.
   */
  protected function saveEntityWithUnpublishedStatus(EntityInterface $entity, $gid, EntityInterface $existingEntity = NULL) {
    if ($this->isPublishable($entity)) {

      if (!$entity->id()) {
        if (ImportProcessor::getCurrentImportProcessor()
          ->getCreationType() == ImportProcessor::PUBLICATION_UNPUBLISH
        ) {
          $entity->setPublished(FALSE);
        }
        else {
          $entity->setPublished(TRUE);
        }
      }
    }

    $this->defaultSave($entity, $existingEntity);
  }

  /**
   * Save without publish status care.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to save.
   * @param \Drupal\Core\Entity\EntityInterface $existingEntity
   *   The existing entity before changes.
   */
  protected function defaultSave(EntityInterface $entity, EntityInterface $existingEntity = NULL) {
    if ($this->haveToSave($entity, $existingEntity)) {
      if (is_null($entity->uuid())) {
        $entity->uuid = \Drupal::service('uuid')->generate();
      }

      $entity->save();
    }
  }

  /**
   * Check if the entity is publishable.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check.
   *
   * @return bool
   *   The publishable state.
   */
  protected function isPublishable(EntityInterface $entity) {
    return method_exists($entity, 'setPublished');
  }

  /**
   * Return TRUE if the entity has to be updated.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The imported entity.
   * @param \Drupal\Core\Entity\EntityInterface $existingEntity
   *   The entity before update.
   *
   * @return bool
   *   The state of update.
   */
  protected function haveToSave(EntityInterface $entity, EntityInterface $existingEntity = NULL) {
    $haveToSave = TRUE;

    // Update :
    if ($entity->id()) {
      switch (ImportProcessor::getCurrentImportProcessor()->getUpdateType()) {
        case ImportProcessor::UPDATE_IF_RECENT:
          if ($existingEntity) {
            if (method_exists($entity, 'getChangedTime')) {
              $haveToSave = $entity->getChangedTime() > $existingEntity->getChangedTime();
            }
            else {
              $haveToSave = TRUE;
            }
          }
          else {
            $haveToSave = TRUE;
          }
          break;

        case ImportProcessor::UPDATE_NO_UPDATE:
          $haveToSave = FALSE;
          break;

        case ImportProcessor::UPDATE_SYSTEMATIC:
        default:
          $haveToSave = TRUE;
      }
    }

    return $haveToSave;
  }

}
