<?php

namespace Drupal\entity_log;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Entity log entities.
 *
 * @ingroup entity_log
 */
class EntityLogListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Entity log ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\entity_log\Entity\EntityLog */
    $row['id'] = $entity->id();

    $row['name'] = Link::createFromRoute($entity->label(), 'entity.entity_log.edit_form', [
      'entity_log' => $entity->id(),
    ]);
    return $row + parent::buildRow($entity);
  }

}
