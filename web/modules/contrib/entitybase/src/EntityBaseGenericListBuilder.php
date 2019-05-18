<?php

/**
 * @file
 * Contains \Drupal\entity_base\EntityBaseGenericListBuilder.
 */

namespace Drupal\entity_base;

use Drupal\Core\Entity\EntityInterface;

/**
 * List controller for entities.
 */
class EntityBaseGenericListBuilder extends EntityBaseSimpleListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = parent::buildHeader();

    $this->arrayInsert($header, 'owner', [
      'type' => [
        'data' => $this->t('Type'),
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
        'field' => 'type',
        'specifier' => 'type',
      ]
    ]);

    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row = parent::buildRow($entity);

    $this->arrayInsert($row, 'owner', [
      'type' => $entity->getType(),
    ]);

    return $row;
  }

}
