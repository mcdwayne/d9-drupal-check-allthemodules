<?php

namespace Drupal\visualn_drawing;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of VisualN Drawing entities.
 *
 * @ingroup visualn_drawing
 */
class VisualNDrawingListBuilder extends EntityListBuilder {


  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('VisualN Drawing ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\visualn_drawing\Entity\VisualNDrawing */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.visualn_drawing.edit_form',
      ['visualn_drawing' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
