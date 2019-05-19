<?php

namespace Drupal\task;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Task Closure Reason entities.
 */
class TaskClosureReasonListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Task Closure Reason');
    $header['id'] = $this->t('Machine name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    // You probably want a few more properties here...
    $row = $row + parent::buildRow($entity);
    if ($entity->isLocked()) {
      $row['operations'] = t('Locked for Editing');
    }
    return $row;
  }

}
