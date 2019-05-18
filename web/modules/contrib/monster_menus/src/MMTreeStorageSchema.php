<?php

namespace Drupal\monster_menus;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;

/**
 * Defines the entity schema handler.
 */
class MMTreeStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $schema = parent::getEntitySchema($entity_type, $reset);

    $base = $entity_type->getBaseTable();
    $schema[$base]['indexes'] += [
      'name'            => ['name'],
      'sort_idx'        => ['sort_idx'],
      'sort_idx_dirty'  => ['sort_idx_dirty'],
      'alias'           => ['alias'],
      'weight'          => ['weight'],
      'parent_sort_idx' => ['parent', 'sort_idx'],
    ];

    // For some reason, $entity_type->getRevisionTable() doesn't work.
    $schema[$base . '_revision']['indexes'] += [
      'alias'           => ['alias'],
    ];

    return $schema;
  }

}
