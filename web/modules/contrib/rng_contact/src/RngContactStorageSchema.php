<?php

namespace Drupal\rng_contact;

use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the RNG Contact schema handler.
 */
class RngContactStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * @inheritDoc
   */
  protected function hasSharedTableNameChanges(EntityTypeInterface $entity_type, EntityTypeInterface $original) {
    if (drupal_static('rng_contact_update_8001')) {
      // We are changing the 'data_table', but the table never existed in the
      // first place. The annotation we set erroneously. Safely pretend nothing
      // changed, so the entity manager accepts the new value.
      // @see rng_contact_update_8001().
      return FALSE;
    }
    return parent::hasSharedTableNameChanges($entity_type, $original);
  }

}