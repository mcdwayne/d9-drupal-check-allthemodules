<?php

/**
 * @file
 * Contains \Drupal\wisski_salz\AdapterListBuilder.
 */

namespace Drupal\wisski_salz;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of WissKI Salz Adapter entities.
 */
class AdapterListBuilder extends ConfigEntityListBuilder {
  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('WissKI Salz Adapter');
    $header['id'] = $this->t('Machine name');
    $header['is_preferred_local'] = $this->t('Preferred Local Store');
    $header['is_writable'] = $this->t('Writable');
    $header['description'] = $this->t('Description');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['is_preferred_local_store'] = $this->tickMark($entity->getEngine()->isPreferredLocalStore());
    $row['is_writable'] = $this->tickMark($entity->getEngine()->isWritable());
    $row['description'] = $entity->getDescription();
    // You probably want a few more properties here...
    return $row + parent::buildRow($entity);
  }
  
  private function tickMark($check) {
    
    if ($check) return $this->t('&#10004;');
    return $this->t('&#10008;');
  }

}
