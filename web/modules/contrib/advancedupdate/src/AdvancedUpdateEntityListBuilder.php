<?php

namespace Drupal\advanced_update;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Advanced update entity entities.
 */
class AdvancedUpdateEntityListBuilder extends ConfigEntityListBuilder {

  /**
   * Entities state by module.
   *
   * @var array
   */
  protected $entityStates = array();

  /**
   * {@inheritdoc}
   */
  public function load() {
    $advanced_update_manager = \Drupal::service('advanced_update.advanced_update_manager');
    $this->entityStates = $advanced_update_manager->getUpdateState();
    $query = $this->getStorage()
      ->getQuery()
      ->sort($this->entityType->getKey('date'), 'DESC');
    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }
    $entity_ids = $query->execute();
    return $this->storage->loadMultipleOverrideFree($entity_ids);
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['date'] = $this->t('Creation date');
    $header['label'] = $this->t('Description');
    $header['class_name'] = $this->t('Class name');
    $header['module_name'] = $this->t('Module name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['date'] = date('d-m-Y H:i:s', $entity->date());
    $row['label'] = $entity->label();
    $row['class_name'] = $entity->className();
    $row['module_name'] = $entity->moduleName();

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $advanced_update_manager = \Drupal::service('advanced_update.advanced_update_manager');
    if ($advanced_update_manager->isAvailableUpdate($advanced_update_manager::UP, $this->entityStates, $entity)) {
      return parent::getOperations($entity);
    }
    else {
      return array();
    }
  }

}
