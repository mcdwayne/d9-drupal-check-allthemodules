<?php

namespace Drupal\multiversion\Field;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\UuidItem as CoreUuidItem;

class UuidItem extends CoreUuidItem {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);
    // With Multiversion the there can be multiple representation of the same
    // universal entity in the storage, e.g. one entity in each workspace.
    // So we have to remove the unique key for this field in the DB.
    unset($schema['unique keys']['value']);
    return $schema;
  }
}
