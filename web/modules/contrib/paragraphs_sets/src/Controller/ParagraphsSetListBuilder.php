<?php

namespace Drupal\paragraphs_sets\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of ParagraphsSet.
 */
class ParagraphsSetListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['icon_file'] = [
      'data' => $this->t('Icon'),
    ];
    $header['label'] = $this->t('Label');
    $header['id'] = $this->t('Machine name');
    $header['description'] = $this->t('Description');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['icon_file'] = [];
    if ($icon_url = $entity->getIconUrl()) {
      $row['icon_file']['class'][] = 'paragraphs-set-icon';
      $row['icon_file']['data'] = [
        '#theme' => 'image',
        '#uri' => $icon_url,
        '#width' => 32,
        '#height' => 32,
      ];
    }
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['description']['data'] = ['#markup' => $entity->getDescription()];
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    /** @var \Drupal\field\FieldConfigInterface $entity */
    $operations = parent::getDefaultOperations($entity);

    if (isset($operations['edit'])) {
      $operations['edit']['weight'] = 30;
    }

    return $operations;
  }

}
