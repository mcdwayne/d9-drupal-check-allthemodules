<?php

namespace Drupal\formazing;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Result formazing entity entities.
 *
 * @ingroup formazing
 */
class ResultFormazingEntityListBuilder extends EntityListBuilder {


  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Result formazing entity ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\formazing\Entity\ResultFormazingEntity */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute($entity->label(), 'entity.result_formazing_entity.edit_form', ['result_formazing_entity' => $entity->id()]);
    return $row + parent::buildRow($entity);
  }

}
