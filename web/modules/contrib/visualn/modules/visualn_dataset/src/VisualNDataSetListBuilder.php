<?php

namespace Drupal\visualn_dataset;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of VisualN Data Set entities.
 *
 * @ingroup visualn_dataset
 */
class VisualNDataSetListBuilder extends EntityListBuilder {


  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('VisualN Data Set ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\visualn_dataset\Entity\VisualNDataSet */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.visualn_dataset.edit_form',
      ['visualn_dataset' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
