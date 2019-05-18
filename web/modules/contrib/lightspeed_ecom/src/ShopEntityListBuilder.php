<?php

namespace Drupal\lightspeed_ecom;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Lightspeed eCom Shop entities.
 */
class ShopEntityListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Name');
    $header['id'] = $this->t('Machine name');
    $header['cluster_id'] = $this->t('Cluster ID');
    $header['api_key'] = $this->t('API Key');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['cluster_id'] = $entity->get('cluster_id');
    $row['api_key'] = $entity->get('api_key');
    return $row + parent::buildRow($entity);
  }

}
