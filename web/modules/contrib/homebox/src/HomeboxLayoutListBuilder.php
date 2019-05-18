<?php

namespace Drupal\homebox;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Homebox Layout entities.
 *
 * @ingroup homebox
 */
class HomeboxLayoutListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Homebox Layout ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\homebox\Entity\HomeboxLayout */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.homebox_layout.edit_form',
      ['homebox_layout' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
