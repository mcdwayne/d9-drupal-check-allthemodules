<?php

namespace Drupal\entity_extra\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * A list builder for config entities. It displays a column with the entity's 
 * machine name alongside its label.
 */
class ConfigEntityListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $row = [
      'label' => '',
    ] + parent::buildHeader();
    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    // Adds a column with the entity's label and ID.
    $data = [
      '#markup' => $entity->label() . ' <small>(' . $entity->id() . ')</small>',
    ];
    $row = [
      'label' => [
        'data' => $data,
      ],
    ] + parent::buildRow($entity);
    return $row;    
  }

}
