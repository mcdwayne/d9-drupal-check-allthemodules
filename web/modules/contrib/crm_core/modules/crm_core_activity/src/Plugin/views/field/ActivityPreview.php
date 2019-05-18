<?php

namespace Drupal\crm_core_activity\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to present the preview for the activity.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("activity_preview")
 */
class ActivityPreview extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    return $this->getEntity($values)->type->entity->getPlugin()->display($this->getEntity($values));
  }

}
