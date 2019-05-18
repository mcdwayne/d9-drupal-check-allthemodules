<?php

namespace Drupal\altruja\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of altruja blocks.
 */
class AltrujaBlockListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Altruja block');
    $header['id'] = $this->t('Machine name');
    $header['placeholder'] = $this->t('Placeholder');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $this->getLabel($entity);
    $row['id'] = $entity->id;
    $row['placeholder'] = $entity->getPlaceholder();
    return $row + parent::buildRow($entity);
  }

}
