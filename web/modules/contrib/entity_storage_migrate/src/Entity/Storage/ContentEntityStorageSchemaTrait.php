<?php

namespace Drupal\entity_storage_migrate\Entity\Storage;

use Drupal\Core\Entity\EntityTypeInterface;

trait ContentEntityStorageSchemaTrait {

  /**
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   * @param \Drupal\Core\Entity\EntityTypeInterface $original
   */
  public function onEntityTypeUpdate(EntityTypeInterface $entity_type, EntityTypeInterface $original) {
    // @todo: Override parent and support migration between storage handlers.
    return parent::onEntityTypeUpdate($entity_type, $original);
  }

}
