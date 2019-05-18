<?php

namespace Drupal\cacheflush_ui;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Defines a class to build a listing of Cacheflush entity entities.
 *
 * @ingroup cacheflush_ui
 */
class CacheflushEntityListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Cacheflush entity ID - List Builder');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\cacheflush_entity\Entity\CacheflushEntity */
    $row['id'] = $entity->id();
    return $row + parent::buildRow($entity);
  }

}
