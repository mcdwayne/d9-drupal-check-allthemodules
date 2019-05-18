<?php

namespace Drupal\contest;

use Drupal;
use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines a class to build a listing of user role entities.
 *
 * @see \Drupal\user\Entity\Role
 */
class ContestListBuilder extends DraggableListBuilder {

  /**
   * Overrides EntityListController::buildHeader().
   *
   * @return array
   *   The header descriptor.
   */
  public function buildHeader() {
    $header = [
      'created'    => t('Created'),
      'operations' => t('Operations'),
    ];
    return $header + parent::buildHeader();
  }

  /**
   * Overrides EntityListController::buildRow().
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The contest entity.
   *
   * @return array
   *   An array of fields in the row.
   */
  public function buildRow(EntityInterface $entity) {
    $row = ['created' => $entity->getCreated() ? Drupal::service('date.formatter')->format($entity->getCreated(), 'long') : t('n/a')];

    return $row + parent::buildRow($entity);
  }

  /**
   * Get the form ID.
   *
   * @return string
   *   The form's ID.
   */
  public function getFormId() {
    return 'contest_list_form';
  }

  /**
   * Impliments the DraggableListBuilder operations.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The contest entity.
   *
   * @todo Find out what this returns.
   */
  public function getOperations(EntityInterface $entity) {
    return TRUE ? parent::getOperations($entity) : NULL;
  }

  /**
   * See \Drupal\Core\Config\Entity\ConfigEntityBase::sort().
   *
   * @return array
   *   An array of sorted contests.
   */
  public function load() {
    $entities = $this->storage->loadMultiple();

    uasort($entities, [$this->entityType->getClass(), 'sort']);

    return $entities;
  }

}
