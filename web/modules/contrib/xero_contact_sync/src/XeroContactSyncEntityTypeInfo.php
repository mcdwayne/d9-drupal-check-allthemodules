<?php

namespace Drupal\xero_contact_sync;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Alters entity type info.
 *
 * @see \xero_contact_sync_entity_base_field_info()
 *
 * @package Drupal\xero_contact_sync
 */
class XeroContactSyncEntityTypeInfo {

  public function entityBaseFieldInfo(EntityTypeInterface $entity_type) {
    $fields = [];
    if ($entity_type->id() === 'user') {
      $fields['xero_contact_id'] = BaseFieldDefinition::create('string')
        ->setLabel(t('Xero contact id'))
        ->setDescription(t('The Xero contact id.'));
    }
    return $fields;
  }

}
