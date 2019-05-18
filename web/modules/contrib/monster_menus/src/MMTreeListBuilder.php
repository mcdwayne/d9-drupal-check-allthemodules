<?php

namespace Drupal\monster_menus;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of MM Page entities.
 *
 * @ingroup monster_menus
 */
class MMTreeListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('MM Page ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\monster_menus\Entity\MMTree */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute($entity->label(), 'entity.mm_tree.edit_form', ['mm_tree' => $entity->id()]);
    return $row + parent::buildRow($entity);
  }

}
