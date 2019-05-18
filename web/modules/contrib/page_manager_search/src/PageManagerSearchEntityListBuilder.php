<?php

/**
 * @file
 */

namespace Drupal\page_manager_search;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Page Manager Search entities.
 *
 * @ingroup page_manager_search
 */
class PageManagerSearchEntityListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Page Manager Search ID');
    $header['title'] = $this->t('Title');
    $header['content'] = $this->t('Content');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['id'] = $entity->id();
    $row['title'] = $entity->label();
    $row['content'] = $entity->get('content');

    return $row + parent::buildRow($entity);
  }

}
