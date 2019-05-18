<?php

namespace Drupal\searchcloud_block\Entity\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListController;

class SearchCloudListController extends EntityListController {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['keyword'] = t('Keyword');
    $header['count']   = t('Count');
    $header['hide']    = t('Hidden/shown');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\searchcloud_block\Entity\SearchCloud */
    $row['keyword'] = $entity->keyword->value;
    $row['count']   = $entity->count->value;
    $row['hide']    = (empty($entity->hide->value) ? $this->t('Shown') : $this->t('Hidden'));

    return $row + parent::buildRow($entity);
  }

}