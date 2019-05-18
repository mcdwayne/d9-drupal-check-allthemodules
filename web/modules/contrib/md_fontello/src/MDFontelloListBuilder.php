<?php

namespace Drupal\md_fontello;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Provides a listing of MDFontello entities.
 */
class MDFontelloListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('MDFontello');
    $header['id'] = $this->t('Machine name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    return $row + parent::buildRow($entity);
  }

  public function getOperations(EntityInterface $entity) {
    $operations = parent::getOperations($entity);
    $view = [
        'title' => t('View'),
        'weight' => 1,
        'url' => Url::fromRoute('md_fontello.view', ['font' => $entity->id()]),
    ];
    array_unshift($operations, $view);
    return $operations;
  }
}
