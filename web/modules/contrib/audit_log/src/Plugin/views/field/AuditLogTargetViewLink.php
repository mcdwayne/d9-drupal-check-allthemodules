<?php

namespace Drupal\audit_log\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Provides a target entity view link.
 *
 * @ViewsField("audit_log_target_view_link")
 */
class AuditLogTargetViewLink extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $row) {
    $entity = $this->getEntity($row);

    $target_entity = \Drupal::entityTypeManager()->getStorage($entity->entity_type->value)->load($entity->entity_id->target_id);

    $build = [
      '#cache' => [
        'tags' => [
          $entity->entity_type->value . ':' . $entity->entity_id->target_id,
        ],
      ],
    ];

    if (isset($target_entity)) {
      return $build + $target_entity->toLink($entity->label())->toRenderable();
    }
    else {
      return $build + [
        '#markup' => $entity->label(),
      ];
    }
  }

}
