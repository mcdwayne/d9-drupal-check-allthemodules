<?php

namespace Drupal\cumulio;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Cumulio entity entities.
 *
 * @ingroup cumulio
 */
class CumulioEntityListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Cumulio entity ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\cumulio\Entity\CumulioEntity */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.cumulio_entity.edit_form',
      ['cumulio_entity' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
