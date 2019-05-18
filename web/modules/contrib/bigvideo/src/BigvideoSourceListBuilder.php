<?php

namespace Drupal\bigvideo;

use Drupal\bigvideo\Entity\BigvideoSourceInterface;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of BigVideo Source entities.
 */
class BigvideoSourceListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Source');
    $header['id'] = $this->t('Machine name');
    $header['type'] = $this->t('Type');
    $header['mp4'] = $this->t('MP4');
    $header['webm'] = $this->t('WebM');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\bigvideo\Entity\BigvideoSourceInterface $entity */
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['type'] = ($entity->getType() == BigvideoSourceInterface::TYPE_FILE) ? $this->t('File') : $this->t('Link');

    $links = $entity->createVideoLinks();
    $row['mp4'] = isset($links['mp4']) ? $links['mp4'] : '';
    $row['webm'] = isset($links['webm']) ? $links['webm'] : '';
    return $row + parent::buildRow($entity);
  }

}
