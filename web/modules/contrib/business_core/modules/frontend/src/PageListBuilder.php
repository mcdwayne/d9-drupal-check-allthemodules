<?php

namespace Drupal\frontend;

use Drupal\Core\Entity\EntityInterface;

class PageListBuilder extends ContainerListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $parent = parent::buildHeader();

    $header['label'] = $parent['label'];
    unset($parent['label']);

    $header['layout'] = t('Layout');
    $header['path'] = t('Path');

    return $header + $parent;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $parent = parent::buildRow($entity);

    $row['label'] = $parent['label'];
    unset($parent['label']);

    $row['layout'] = $entity->getLayout();
    $row['path'] = $entity->getPath();

    return $row + $parent;
  }

}
