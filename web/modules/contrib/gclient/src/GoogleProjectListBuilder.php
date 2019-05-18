<?php

namespace Drupal\gclient;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines the list builder.
 */
class GoogleProjectListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->entityType->getLabel();
    $header['project_id'] = $this->t('Project ID');
    $header['project_number'] = $this->t('Project Number');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->toLink($entity->label(), 'edit-form');
    $row['project_id'] = $entity->get('project_id');
    $row['project_number'] = $entity->get('project_number');
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $operations = parent::getOperations($entity);
    // Place the edit operation after the operations added by field_ui.module
    // which have the weights 15, 20, 25.
    if (isset($operations['edit'])) {
      $operations['edit'] = [
        'title' => t('Edit'),
        'weight' => 30,
        'url' => $entity->toUrl('edit-form')
      ];
    }
    if (isset($operations['delete'])) {
      $operations['delete'] = [
        'title' => t('Delete'),
        'weight' => 35,
        'url' => $entity->toUrl('delete-form')
      ];
    }
    // Sort the operations to normalize link order.
    uasort($operations, [
      'Drupal\Component\Utility\SortArray',
      'sortByWeightElement'
    ]);

    return $operations;
  }

}
