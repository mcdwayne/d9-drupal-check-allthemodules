<?php

namespace Drupal\menu_megadrop;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Menu megadrop entities.
 *
 * @ingroup menu_megadrop
 */
class MenuMegadropListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Menu megadrop ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\menu_megadrop\Entity\MenuMegadrop */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.menu_megadrop.edit_form',
      ['menu_megadrop' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
