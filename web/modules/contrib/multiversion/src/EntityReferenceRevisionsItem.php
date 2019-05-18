<?php

namespace Drupal\multiversion;

use Drupal\entity_reference_revisions\EntityNeedsSaveInterface;
use Drupal\entity_reference_revisions\Plugin\Field\FieldType\EntityReferenceRevisionsItem as ContribEntityReferenceRevisionsItem;

/**
 * Alternative entity reference revisions base field item type class.
 */
class EntityReferenceRevisionsItem extends ContribEntityReferenceRevisionsItem {

  use EntityReferenceFieldTrait {
    preSave as entityReferencePreSave;
  }

  /**
   * Change the logic around saving auto-created entities.
   *
   * @see \Drupal\multiversion\EntityReferenceFieldTrait::preSave()
   * @see \Drupal\entity_reference_revisions\Plugin\Field\FieldType\EntityReferenceRevisionsItem::preSave()
   */
  public function preSave() {
    if (!$this->parentIsEnabledEntityType()) {
      // Call source class method if parent entity isn't supported by Multiversion.
      parent::preSave();
      return;
    }

    $has_new = $this->hasNewEntity();

    // If it is a new entity, parent will save it.
    $this->entityReferencePreSave();

    if (!$has_new) {
      // Create a new revision if it is a composite entity in a host with a new
      // revision.
      $host = $this->getEntity();
      $needs_save = $this->entity instanceof EntityNeedsSaveInterface && $this->entity->needsSave();
      if (!$host->isNew() && $host->isNewRevision() && $this->entity && $this->entity->getEntityType()->get('entity_revision_parent_id_field')) {
        $this->entity->setNewRevision();
        if ($host->isDefaultRevision()) {
          $this->entity->isDefaultRevision(TRUE);
        }
        $needs_save = TRUE;
      }
      if ($needs_save) {
        // Delete the paragraph when the host entity is deleted.
        if ($host->_deleted->value == TRUE) {
          $this->entity->delete();
        }
        // We need special handling for entities with paragraphs fields during
        // replication (when $host->_rev->new_edit is FALSE).
        elseif ($host->_rev->new_edit == FALSE) {
          /** @var \Drupal\multiversion\MultiversionManagerInterface $multiversion_manager */
          $multiversion_manager = \Drupal::service('multiversion.manager');
          $entity_type = $this->entity->getEntityType();
          if ($multiversion_manager->isEnabledEntityType($entity_type)) {
            /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
            $entity_type_manager = \Drupal::service('entity_type.manager');
            /** @var \Drupal\multiversion\Entity\Storage\ContentEntityStorageInterface $storage */
            $storage = $entity_type_manager->getStorage($this->entity->getEntityTypeId());
            $entities = $storage->loadByProperties(['uuid' => $this->entity->uuid()]);
            $entity = reset($entities);
            if ($entity) {
              $parent_id = $this->entity->getEntityType()->get('entity_revision_parent_id_field');
              $entity->set($parent_id, $this->entity->id());
              $entity->setNewRevision(FALSE);
              $this->entity = $entity;
            }
            $storage->saveWithoutForcingNewRevision($this->entity);
          }
        }
        else {
          $this->entity->save();
        }
      }
    }
    if ($this->entity) {
      $this->target_revision_id = $this->entity->getRevisionId();
    }
  }

  /**
   * Change the logic around revisions handling.
   *
   * By default multiversion storage forces new revision on entity save.
   * But this should be not done on "postSave" call, as we will finish the save
   * process only after this method call.
   *
   * @see \Drupal\entity_reference_revisions\Plugin\Field\FieldType\EntityReferenceRevisionsItem::postSave()
   * @see \Drupal\multiversion\Entity\Storage\ContentEntityStorageTrait::saveWithoutForcingNewRevision()
   */
  public function postSave($update) {
    $needs_save = FALSE;
    // If any of entity, parent type or parent id is missing then return.
    if (!$this->entity
      || !$this->entity->getEntityType()->get('entity_revision_parent_type_field')
      || !$this->entity->getEntityType()->get('entity_revision_parent_id_field')) {
      return;
    }

    if (!$this->parentIsEnabledEntityType()) {
      // Call source class method if parent entity isn't supported by Multiversion.
      parent::postSave($update);
      return;
    }

    $entity = $this->entity;
    $parent_entity = $this->getEntity();

    // If the entity has a parent field name get the key.
    if ($entity->getEntityType()->get('entity_revision_parent_field_name_field')) {
      $parent_field_name = $entity->getEntityType()->get('entity_revision_parent_field_name_field');

      // If parent field name has changed then set it.
      if ($entity->get($parent_field_name)->value != $this->getFieldDefinition()->getName()) {
        $entity->set($parent_field_name, $this->getFieldDefinition()->getName());
        $needs_save = TRUE;
      }
    }

    $parent_type = $entity->getEntityType()->get('entity_revision_parent_type_field');
    $parent_id = $entity->getEntityType()->get('entity_revision_parent_id_field');

    // If the parent type has changed then set it.
    if ($entity->get($parent_type)->value != $parent_entity->getEntityTypeId()) {
      $entity->set($parent_type, $parent_entity->getEntityTypeId());
      $needs_save = TRUE;
    }
    // If the parent id has changed then set it.
    if ($entity->get($parent_id)->value != $parent_entity->id()) {
      $entity->set($parent_id, $parent_entity->id());
      $needs_save = TRUE;
    }

    if ($needs_save) {
      // Check if any of the keys has changed, save it, do not create a new
      // revision.
      $entity->setNewRevision(FALSE);
      /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
      $entity_type_manager = \Drupal::service('entity_type.manager');
      /** @var \Drupal\multiversion\MultiversionManagerInterface $multiversion_manager */
      $multiversion_manager = \Drupal::service('multiversion.manager');
      $entity_type_id = $entity->getEntityTypeId();
      $entity_type = $entity_type_manager->getDefinition($entity_type_id);

      if ($multiversion_manager->isEnabledEntityType($entity_type)) {
        /** @var \Drupal\multiversion\Entity\Storage\ContentEntityStorageInterface $storage */
        $storage = $entity_type_manager->getStorage($entity_type_id);
        $storage->saveWithoutForcingNewRevision($entity);
      }
    }
  }

  /**
   * Checks whether parent entity is supported by Multiversion or not.
   *
   * @return bool
   *   TRUE if parent entity is supported by Multiversion, FALSE otherwise.
   */
  protected function parentIsEnabledEntityType() {
    $parent_entity = $this->getEntity();
    $parent_entity_type = $parent_entity->getEntityType();
    if (\Drupal::service('multiversion.manager')->isEnabledEntityType($parent_entity_type)) {
      return TRUE;
    }

    return FALSE;
  }

}
