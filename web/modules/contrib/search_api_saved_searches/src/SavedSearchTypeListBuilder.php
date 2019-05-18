<?php

namespace Drupal\search_api_saved_searches;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Builds a listing of saved search type entities.
 */
class SavedSearchTypeListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    return [
      'label' => $this->t('Name'),
      'description' => $this->t('Description'),
      'status' => $this->t('Status'),
    ] + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\search_api_saved_searches\SavedSearchTypeInterface $entity */
    $status = $entity->status();
    $status_label = $status ? $this->t('Enabled') : $this->t('Disabled');
    $status_icon = [
      '#theme' => 'image',
      '#uri' => $status ? 'core/misc/icons/73b355/check.svg' : 'core/misc/icons/e32700/error.svg',
      '#width' => 18,
      '#height' => 18,
      '#alt' => $status_label,
      '#title' => $status_label,
    ];

    $row = [
      'data' => [
        'label' => $entity->label(),
        'description' => [
          'data' => [
            '#markup' => $entity->getDescription(),
          ],
        ],
        'status' => [
          'data' => $status_icon,
        ],
      ],
      'title' => $this->t('ID: @name', ['@name' => $entity->id()]),
      'class' => [
        $status ? 'search-api-list-enabled' : 'search-api-list-disabled',
      ],
    ];
    $row['data'] += parent::buildRow($entity);

    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $build['#attached']['library'][] = 'search_api/drupal.search_api.admin_css';
    return $build;
  }

}
