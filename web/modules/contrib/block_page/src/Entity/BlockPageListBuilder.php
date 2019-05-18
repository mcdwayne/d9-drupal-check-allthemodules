<?php

/**
 * @file
 * Contains \Drupal\block_page\Entity\BlockPageListBuilder.
 */

namespace Drupal\block_page\Entity;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a list builder for block pages.
 */
class BlockPageListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    $header['id'] = $this->t('Machine name');
    $header['path'] = $this->t('Path');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var $entity \Drupal\block_page\BlockPageInterface */
    $row['label'] = $this->getLabel($entity);
    $row['id'] = $entity->id();
    $row['path']['data'] = array(
      '#type' => 'link',
      '#href' => $entity->getPath(),
      '#title' => $entity->getPath(),
    );
    return $row + parent::buildRow($entity);
  }

}
