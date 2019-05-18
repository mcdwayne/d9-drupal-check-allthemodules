<?php

namespace Drupal\cbo_inventory\Plugin\views\filter;

use Drupal\cbo_inventory\Entity\Subinventory as SubinventoryEntity;
use Drupal\views\Plugin\views\filter\InOperator;

/**
 * Filter handler for inventory.
 *
 * @ViewsFilter("subinventory")
 */
class Subinventory extends InOperator {

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    $entities = SubinventoryEntity::loadMultiple();
    $this->valueOptions = array_map(function ($entity) {
      $label = $entity->label();
      $description = $entity->get('description')->value;
      if (!empty($description)) {
        $label = $label . ' ' . $description;
      }
      return $label;
    }, $entities);

    return $this->valueOptions;
  }

}
