<?php

namespace Drupal\file_management\Plugin\views\field;

use Drupal\views\ResultRow;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\field\EntityOperations;

/**
 * Renders all operations links for a file.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("file_management_operations")
 */
class FileManagementOperations extends EntityOperations {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $entity = $this->getEntityTranslation($this->getEntity($values), $values);

    $operations = [
      'edit' => [
        'title' => $this->t('Edit'),
        'url' => Url::fromRoute('file_management.edit_page', [
          'file' => $entity->id(),
        ]),
        'weight' => 1,
      ],
      'view' => [
        'title' => $this->t('View'),
        'url' => Url::fromRoute('file_management.view_page', [
          'file' => $entity->id(),
        ]),
        'weight' => 2,
      ],
      'delete' => [
        'title' => $this->t('Delete'),
        'url' => Url::fromRoute('file_management.delete_page', [
          'file' => $entity->id(),
        ]),
        'weight' => 3,
      ]
    ];

    if ($this->options['destination']) {
      foreach ($operations as &$operation) {
        if (!isset($operation['query'])) {
          $operation['query'] = [];
        }
        $operation['query'] += $this->getDestinationArray();
      }
    }

    $build = [
      '#type' => 'operations',
      '#links' => $operations,
    ];

    return $build;
  }

}
