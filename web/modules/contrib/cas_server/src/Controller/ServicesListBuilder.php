<?php

/**
 * @file
 * Contains \Drupal\cas_server\Controller\ServicesListBuilder.
 */

namespace Drupal\cas_server\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of CasServerServices.
 */
class ServicesListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Service');
    $header['id'] = $this->t('Machine name');
    $header['pattern'] = $this->t('Service URL pattern');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $this->getLabel($entity);
    $row['id'] = $entity->getId();
    $row['pattern'] = $entity->getService();

    return $row + parent::buildRow($entity);
  }
}
