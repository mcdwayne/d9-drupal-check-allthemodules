<?php
/**
 * @file
 * Contains Drupal\widget_block\WidgetBlockListBuilder.
 */

namespace Drupal\widget_block;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Defines a class to build a listing of widget block configuration entities.
 */
class WidgetBlockListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    // Build the list header and merge in default headers.
    return ['label' => $this->t('Label')] + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    // Initialize $row variable to an empty array.
    $row = [];
    // Set the entity label.
    $row['label'] = $entity->label();
    // Merge in the default entity related columns.
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    // Get the default operations.
    $operations = parent::getDefaultOperations($entity);
    // Check whether edit operation is supported.
    if (isset($operations['edit'])) {
      // Set the default destination when editing a entity.
      $operations['edit']['query']['destination'] = $entity->url('collection');
    }

    return $operations;
  }

}
