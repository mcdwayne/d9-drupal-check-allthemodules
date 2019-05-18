<?php

namespace Drupal\loading_bar_library;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of loading bar style entities.
 *
 * @see \Drupal\loading_bar_library\Entity\LoadingBarStyle
 */
class LoadingBarStyleListBuilder extends ConfigEntityListBuilder {

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
    /** @var \Drupal\loading_bar_library\Entity\LoadingBarStyleInterface $entity */
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
      ':link' => Url::fromRoute('entity.loading_bar_style.add_form')->toString(),
    ];
    $build['table']['#empty'] = $this->t('No loading bar styles available. <a href=":link">Add loading bar style</a>.', $t_args);

    return $build;
  }

}
