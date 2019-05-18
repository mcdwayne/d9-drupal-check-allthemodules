<?php

namespace Drupal\config_views\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to display entity label optionally linked to entity page.
 *
 * @ViewsField("config_entity_operations")
 */
class ConfigEntityOperations extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    // Doesn't work as there is no alias.
    // $value = $this->getValue($values, 'type');.
    $entity_type = $values->type;
    $entity = $values->entity;
    $list_builder = \Drupal::entityTypeManager()->getListBuilder($entity_type);
    return [
      '#type' => 'operations',
      '#links' => $list_builder->getOperations($entity),
    ];
  }

}
