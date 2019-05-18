<?php

namespace Drupal\entity_reference_inline\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;

/**
 * Provides a trait for common methods defined in the field formatter.
 *
 * The methods are provided through a trait in order for them to be reusable.
 */
trait FieldFormatterCommonMethodsTrait {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);

    // Allow modules to modify the view build defaults for the inline entities.
    $field_definition_settings = $items->getFieldDefinition()->getSettings();
    $target_entity_type = $field_definition_settings['target_type'];
    $module_handler = \Drupal::moduleHandler();
    foreach ($elements as $delta => &$element) {
      $inline_entity = $element["#{$target_entity_type}"];
      $view_hook = "{$target_entity_type}_inline_view_build_defaults";
      $field_item = $items->get($delta);
      $module_handler->alter([$view_hook, 'entity_inline_view_build_defaults'], $element, $inline_entity, $field_item);
    }

    return $elements;
  }

}
