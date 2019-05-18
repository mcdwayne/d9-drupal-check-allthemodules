<?php

/**
 * @file
 * Contains Drupal\expressions\Controller\ExpressionListBuilder.
 */

namespace Drupal\expressions\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Expression.
 */
class ExpressionListBuilder extends ConfigEntityListBuilder {
  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Expression');
    $header['id'] = $this->t('Machine name');
    $header['status'] = $this->t('Status');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $this->getLabel($entity);
    $row['id'] = $entity->id();
    $row['status'] = $entity->getStatus() ? $this->t('Enabled') :  $this->t('Disabled');
    return $row + parent::buildRow($entity);
  }

}
