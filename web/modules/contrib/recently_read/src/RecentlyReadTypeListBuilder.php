<?php

namespace Drupal\recently_read;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Recently read type entities.
 */
class RecentlyReadTypeListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Recently read config');
    $header['id'] = $this->t('Machine name');
    $header['enabled'] = $this->t('Enabled');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    if ($entity->get('enabled') == 1) {
      $enabled = "Yes";
    }
    else {
      $enabled = "No";
    }
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['enabled'] = $enabled;
    // You probably want a few more properties here...
    return $row + parent::buildRow($entity);
  }

}
