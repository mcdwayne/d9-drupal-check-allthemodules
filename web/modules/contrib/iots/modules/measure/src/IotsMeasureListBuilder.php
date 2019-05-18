<?php

namespace Drupal\iots_measure;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Iots Measure entities.
 *
 * @ingroup iots_measure
 */
class IotsMeasureListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['created'] = $this->t('Date');
    $header['measure'] = $this->t('Measure');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\iots_measure\Entity\IotsMeasure */
    $row['id'] = $entity->id();
    $row['created'] = format_date($entity->created->value, 'middle');
    $row['measure'] = Link::createFromRoute(
      $entity->label(),
      'entity.iots_measure.edit_form',
      ['iots_measure' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
