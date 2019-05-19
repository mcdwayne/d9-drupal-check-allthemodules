<?php

namespace Drupal\wbm2cm\Plugin\migrate\destination;

use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\migrate\destination\EntityRevision as BaseEntityRevision;
use Drupal\migrate\Row;

/**
 * Fixes bugs in the core EntityRevision destination plugin:
 *
 * 1) getEntity() drops the return value of updateEntity().
 * 2) save() and getIds() do not respect translations.
 * 3) getEntity() decides whether the entity is the default revision.
 *
 * This plugin can be iced once these issues are fixed in core.
 */
class EntityRevision extends BaseEntityRevision {

  /**
   * {@inheritdoc}
   */
  protected function getEntity(Row $row, array $old_destination_id_values) {
    $revision_id = $old_destination_id_values ?
      reset($old_destination_id_values) :
      $row->getDestinationProperty($this->getKey('revision'));
    if (!empty($revision_id) && ($entity = $this->storage->loadRevision($revision_id))) {
      $entity->setNewRevision(FALSE);
    }
    else {
      $entity_id = $row->getDestinationProperty($this->getKey('id'));
      $entity = $this->storage->load($entity_id);

      // If we fail to load the original entity something is wrong and we need
      // to return immediately.
      if (!$entity) {
        return FALSE;
      }

      $entity->enforceIsNew(FALSE);
      $entity->setNewRevision(TRUE);
    }
    return $this->updateEntity($entity, $row) ?: $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids = parent::getIds();

    // A revision could contain multiple translations, so this allows revisions
    // to be identified by language, not just by revision ID.
    if ($this->isTranslationDestination()) {
      if ($key = $this->getKey('langcode')) {
        $ids[$key] = $this->getDefinitionFromEntity($key);
      }
      else {
        throw new MigrateException('This entity type does not support translation.');
      }
    }
    return $ids;
  }

}
