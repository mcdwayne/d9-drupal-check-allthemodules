<?php

namespace Drupal\token_custom;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\token_custom\Entity\TokenCustomType;
use Drupal\Core\Entity\EntityStorageException;

/**
 * Defines the storage for custom_token entities.
 */
class TokenCustomStorage extends SqlContentEntityStorage {

  /**
   * {@inheritdoc}
   */
  protected function doCreate(array $values) {
    // When creating a new custom_token use any custom_token_type.
    if (empty($values['type'])) {
      $types = TokenCustomType::loadMultiple();
      if ($types) {
        $values['type'] = current($types)->id();
      }
      else {
        throw new EntityStorageException('Cannot create token_custom because no token_custom_type has been created.');
      }
    }
    return parent::doCreate($values);
  }

}
