<?php

namespace Drupal\bigvideo;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of BigVideo Page entities.
 */
class BigvideoPageListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Page');
    $header['id'] = $this->t('Machine name');
    $header['path'] = $this->t('Paths');
    $header['status'] = $this->t('Status');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\bigvideo\Entity\BigvideoPageInterface $entity */
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['path'] = $entity->getPath();
    $row['status'] = $entity->status() ? $this->t('Enabled') : $this->t('Disabled');
    return $row + parent::buildRow($entity);
  }

}
