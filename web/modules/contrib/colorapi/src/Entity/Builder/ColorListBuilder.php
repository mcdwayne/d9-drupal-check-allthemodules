<?php

namespace Drupal\colorapi\Entity\Builder;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Colors.
 */
class ColorListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Color');
    $header['id'] = $this->t('Machine name');
    $header['color'] = $this->t('Color');
    $header['hexadecimal'] = $this->t('Hexadecimal Value');
    $header['rgb'] = $this->t('RGB Value');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['color'] = new FormattableMarkup('<div style="height:80%;width:80%;background-color:@color;">&nbsp;</div>', ['@color' => $entity->getHexadecimal()]);
    $row['hexadecimal'] = $entity->getHexadecimal();
    $row['rgb'] = $entity->getRed() . ', ' . $entity->getGreen() . ', ' . $entity->getBlue();

    return $row + parent::buildRow($entity);
  }

}
