<?php

namespace Drupal\owlcarousel2;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of OwlCarousel2 entities.
 *
 * @ingroup owlcarousel2
 */
class OwlCarousel2ListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('OwlCarousel2 ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\owlcarousel2\Entity\OwlCarousel2 */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.owlcarousel2.canonical',
      ['owlcarousel2' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
