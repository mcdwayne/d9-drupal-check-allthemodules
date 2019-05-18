<?php

namespace Drupal\bibcite_entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Defines a class to build a listing of Reference entities.
 *
 * @ingroup bibcite_entity
 */
class ReferenceListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['name'] = $this->t('Title');
    $header['type'] = $this->t('Type');
    $header['uid'] = $this->t('Authored by');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\bibcite_entity\Entity\ReferenceInterface */
    $row['name'] = $entity->toLink();
    $row['type'] = $entity->get('type')->target_id;

    $account = $entity->getOwner();
    $row['uid'] = $account->isAnonymous() ? $account->label() : $account->toLink();
    return $row + parent::buildRow($entity);
  }

}
