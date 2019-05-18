<?php

namespace Drupal\entity_library;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of entity library entities.
 *
 * @see \Drupal\entity_library\Entity\EntityLibrary
 */
class EntityLibraryListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    $header['id'] = $this->t('Machine name');
    $header['description'] = [
      'data' => t('Description'),
      'class' => [RESPONSIVE_PRIORITY_MEDIUM],
    ];

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\entity_library\Entity\EntityLibraryInterface $entity */
    $row['title'] = [
      'data' => $entity->label(),
      'class' => ['menu-label'],
    ];
    $row['id'] = $entity->id();
    $row['description']['data'] = ['#markup' => $entity->getDescription()];

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();

    $t_args = [
      ':link' => Url::fromRoute('entity.entity_library.add_form')->toString(),
    ];
    $build['table']['#empty'] = $this->t('No entity libraries available. <a href=":link">Add entity library</a>.', $t_args);

    return $build;
  }

}
