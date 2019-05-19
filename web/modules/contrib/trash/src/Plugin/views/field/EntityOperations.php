<?php

namespace Drupal\trash\Plugin\views\field;

use Drupal\Core\Url;
use Drupal\views\ResultRow;

/**
 * @ViewsField("trash_content_moderation_state_entity_operations")
 */
class EntityOperations extends \Drupal\views\Plugin\views\field\EntityOperations {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    if (!isset($values->_content_moderated_entity)) {
      return '';
    }
    /** @var \Drupal\content_moderation\ContentModerationStateInterface $entity */
    $entity = $values->_content_moderated_entity;

    $operations = [];
    $operations['restore'] = [
      'title' => t('Restore'),
      'url' => Url::fromRoute('trash.restore_form', [
        'entity_type_id' => $entity->getEntityTypeId(),
        'entity_id' => $entity->id(),
      ], $this->getDestinationArray()),
    ];
    $operations['purge'] = [
      'title' => t('Purge'),
      'url' => Url::fromRoute('trash.purge_form', [
        'entity_type_id' => $entity->getEntityTypeId(),
        'entity_id' => $entity->id(),
      ], $this->getDestinationArray()),
    ];

    $build = [
      '#type' => 'operations',
      '#links' => $operations,
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
  }

}
