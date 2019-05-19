<?php

namespace Drupal\type_style_moderation\Plugin\views\field;

use Drupal\type_style\Plugin\views\field\TypeStyle;
use Drupal\views\ResultRow;

/**
 * A handler to output and arbitrary type style.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("type_style_moderation")
 */
class TypeStyleModeration extends TypeStyle {

  /**
   * Gets the style name for this field.
   *
   * @return string
   *   The style name.
   */
  protected function getStyleName() {
    return str_replace('moderation_state_type_style_', '', $this->realField);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $entity = $this->getEntity($values);
    if (isset($entity->moderation_state)) {
      $module_handler = \Drupal::moduleHandler();
      if (isset($entity->moderation_state->entity) && $module_handler->moduleExists('workbench_moderation')) {
        return type_style_get_style($entity->moderation_state->entity, $this->getStyleName(), '');
      }
      elseif (is_string($entity->moderation_state->value) && $module_handler->moduleExists('content_moderation')) {
        $state_id = $entity->moderation_state->value;
        return type_style_moderation_get_style($entity, 'states', $state_id, $this->getStyleName(), '');
      }
    }
    return '';
  }

}
