<?php

namespace Drupal\elastic_search;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Elastic analyzer entities.
 */
class ElasticAnalyzerListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Elastic analyzer');
    $header['id'] = $this->t('Machine name');
    $header['internal'] = $this->t('Internal');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['internal'] = $entity->isInternal() ? 'yes' : 'no';
    // You probably want a few more properties here...
    return $row + parent::buildRow($entity);
  }

}
