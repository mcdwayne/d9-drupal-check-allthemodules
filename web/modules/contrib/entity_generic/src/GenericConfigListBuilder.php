<?php

namespace Drupal\entity_generic;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;

/**
 * List controller for config entities.
 */
class GenericConfigListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [
      'label' => [
        'data' => $this->entityType->getLabel(),
        'class' => [RESPONSIVE_PRIORITY_LOW],
        'field' => 'created',
        'specifier' => 'created',
      ],
    ];

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->toLink($entity->label(), 'edit-form');
    return $row + parent::buildRow($entity);
  }

  /**
   * Helper function to insert element before specific element.
   * @param array      $array
   * @param int|string $key
   * @param mixed      $insert
   */
  protected function arrayInsert(&$array, $key, $insert) {
    if (is_int($key)) {
      array_splice($array, $key, 0, $insert);
    }
    else {
      $position = array_search($key, array_keys($array));
      $array = array_merge(array_slice($array, 0, $position), $insert, array_slice($array, $position));
    }
  }

}
