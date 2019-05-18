<?php

namespace Drupal\product_choice;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Product choice list entities.
 *
 * @see \Drupal\product_choice\Entity\ProductChoiceList
 */
class ProductChoiceListListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    if (isset($operations['edit'])) {
      $operations['edit']['title'] = t('Edit product choice list');
    }

    $operations['list'] = [
      'title' => t('List terms'),
      'weight' => 0,
      'url' => $entity->urlInfo('terms-list'),
    ];

    unset($operations['delete']);

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('List Name');
    $header['description'] = $this->t('Description');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['description'] = $entity->getDescription();
    return $row + parent::buildRow($entity);
  }

}
