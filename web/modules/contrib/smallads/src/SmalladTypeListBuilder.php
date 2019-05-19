<?php

namespace Drupal\smallads;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines a class to build a listing of smallad type entities.
 *
 * @see \Drupal\smallads\Entity\SmalladType
 */
class SmalladTypeListBuilder extends DraggableListBuilder {

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    // Place the edit operation after the operations added by field_ui.module
    // which have the weights 15, 20, 25.
    if (isset($operations['edit'])) {
      $operations['edit']['weight'] = 30;
    }
    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['smallad_type'] = t('Smallad type');
    $header['description'] = t('Description');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['smallad_type']['data']['#markup'] = SafeMarkup::checkPlain($entity->label());
    $row['description']['data'] = ['#markup' => $entity->getDescription()];
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'smallad_type_weight_form';
  }

}
