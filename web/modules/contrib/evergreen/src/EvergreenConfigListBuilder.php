<?php

namespace Drupal\evergreen;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of EverGreen Config.
 */
class EvergreenConfigListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [
      'entity_type' => $this->t('Entity type'),
      'bundle' => $this->t('Bundle'),
    ];
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row = [
      'entity_type' => $entity->getEvergreenEntityType(),
      'bundle' => $entity->getEvergreenBundle(),
    ];

    return $row + parent::buildRow($entity);
  }

}
