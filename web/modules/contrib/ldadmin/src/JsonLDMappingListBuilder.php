<?php

namespace Drupal\ldadmin;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of JSON-LD Mapping entities.
 */
class JsonLDMappingListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('JSON-LD Mapping Name');
    $header['nid'] = $this->t('Node Path');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['nid'] = 'node/' . $entity->getNid();
    // You probably want a few more properties here...
    return $row + parent::buildRow($entity);
  }

}
