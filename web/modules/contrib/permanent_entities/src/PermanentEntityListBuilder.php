<?php

namespace Drupal\permanent_entities;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Permanent Entity entities.
 *
 * @ingroup permanent_entities
 */
class PermanentEntityListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Permanent Entity ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\permanent_entities\Entity\PermanentEntity */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.permanent_entity.edit_form',
      ['permanent_entity' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
