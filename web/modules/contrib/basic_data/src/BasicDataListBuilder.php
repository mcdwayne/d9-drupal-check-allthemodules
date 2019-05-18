<?php

namespace Drupal\basic_data;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Basic Data entities.
 *
 * @ingroup basic_data
 */
class BasicDataListBuilder extends EntityListBuilder {


  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = [
      'data' => $this->t('ID'),
      'class' => [RESPONSIVE_PRIORITY_LOW]
    ];
    $header['name'] = $this->t('Name');
    $header['type'] = $this->t('Type');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\basic_data\Entity\BasicData */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.basic_data.edit_form',
      ['basic_data' => $entity->id()]
    );
    $row['type'] = $entity->bundle();
    return $row + parent::buildRow($entity);
  }

}
