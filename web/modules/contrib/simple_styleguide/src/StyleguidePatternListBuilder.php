<?php

namespace Drupal\simple_styleguide;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Styleguide pattern entities.
 */
class StyleguidePatternListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    /*$header['pattern'] = $this->t('Pattern');*/
    /*$header['id'] = $this->t('Machine name');*/
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    /*$row['pattern'] = $entity->pattern;*/
    /*$row['id'] = $entity->id();*/

    // You probably want a few more properties here...
    return $row + parent::buildRow($entity);
  }

}
