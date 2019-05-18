<?php

namespace Drupal\reference_map;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Reference Maps.
 */
class ReferenceMapConfigListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Map');
    $header['type'] = $this->t('Type');
    $header['source'] = $this->t('Source');
    $header['destination_type'] = $this->t('Destination Type');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['type'] = $entity->get('type');
    $row['source'] = $entity->sourceType;
    $row['destination_type'] = $entity->destinationType;

    return $row + parent::buildRow($entity);
  }

}
