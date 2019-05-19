<?php

namespace Drupal\svg_maps;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Svg maps entity entities.
 */
class SvgMapsEntityListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['type'] = $this->t('Type');
    $header['label'] = $this->t('Label');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['type'] = $entity->getType()->label();
    $row['label'] = $entity->label();
    // You probably want a few more properties here...
    return $row + parent::buildRow($entity);
  }

}
