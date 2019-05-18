<?php

namespace Drupal\private_page;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a list controller for private_page entity.
 *
 * @ingroup private_page
 */
class PrivatePageListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['path'] = $this->t('Path');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['path'] = $entity->getPrivatePagePath();
    return $row + parent::buildRow($entity);
  }

}
