<?php

namespace Drupal\formazing;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Field formazing entity entities.
 *
 * @ingroup formazing
 */
class FieldFormazingEntityListBuilder extends EntityListBuilder {


  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Field formazing entity ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\formazing\Entity\FieldFormazingEntity */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute($entity->label(), 'entity.field_formazing_entity.edit_form', ['field_formazing_entity' => $entity->id()]);
    return $row + parent::buildRow($entity);
  }

}
