<?php

namespace Drupal\pagedesigner;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Pagedesigner Element entities.
 *
 * @ingroup pagedesigner
 */
class ElementListBuilder extends EntityListBuilder {


  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Pagedesigner Element ID');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\pagedesigner\Entity\Element */
    $row['id'] = $entity->id();
    return $row + parent::buildRow($entity);
  }

}
