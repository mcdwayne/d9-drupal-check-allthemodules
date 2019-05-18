<?php

namespace Drupal\parallax_bg;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Parallax element entities.
 */
class ParallaxElementListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Selector');
    $header['position'] = $this->t('Position');
    $header['speed'] = $this->t('Speed');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\parallax_bg\Entity\ParallaxElementInterface $entity */
    $row['label'] = $entity->label();
    $row['position'] = $entity->getPosition();
    $row['speed'] = $entity->getSpeed();

    // You probably want a few more properties here...
    return $row + parent::buildRow($entity);
  }

}
