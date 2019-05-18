<?php

namespace Drupal\multiversion;

/**
 * Alterations for entity reference field types
 *
 * We use this to replace core entity reference field types to change the
 * logic around saving auto-created entities.
 */
trait EntityReferenceFieldTrait {

  public function preSave() {
    if ($this->hasNewEntity()) {
      // As part of a bulk or replication operation there might be multiple
      // parent entities wanting to auto-create the same reference. So at this
      // point this entity might already be saved, so we look it up by UUID and
      // map it correctly.
      // @see \Drupal\relaxed\BulkDocs\BulkDocs::save()
      if ($this->entity->isNew()) {
        $uuid = $this->entity->uuid();
        $uuid_index = \Drupal::service('multiversion.entity_index.factory')
          ->get('multiversion.entity_index.uuid');
        if ($uuid && $record = $uuid_index->get($uuid)) {
          /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
          $entity_type_manager = \Drupal::service('entity_type.manager');
          $entity_type_id = $this->entity->getEntityTypeId();

          // Now we have to decide what revision to use.
          $id_key = $entity_type_manager
            ->getDefinition($entity_type_id)
            ->getKey('id');

          // If the referenced entity is a stub, but an entity already was
          // created, then load and use that entity instead without saving.
          if ($this->entity->_rev->is_stub && is_numeric($record['entity_id'])) {
            $this->entity = $entity_type_manager
              ->getStorage($entity_type_id)
              ->load($record['entity_id']);
          }
          // If the referenced entity is not a stub then map it with the correct
          // ID from the existing record and save it.
          elseif (!$this->entity->_rev->is_stub) {
            $this->entity->{$id_key}->value = $record['entity_id'];
            $this->entity->enforceIsNew(FALSE);
            $this->entity->save();
          }
        }
        // Just save the entity if no previous record exists.
        else{
          $this->entity->save();
        }
      }
      // Make sure the parent knows we are updating this property so it can
      // react properly.
      if (empty($this->entity) && !empty($record['entity_id'])) {
        $id = $record['entity_id'];
      }
      else {
        $id = $this->entity->id();
      }
      $this->target_id = $id;
    }
    if (!$this->isEmpty() && $this->target_id === NULL) {
      $this->target_id = $this->entity->id();
    }
  }
  
}
