<?php

namespace Drupal\dea\Plugin\Field\FieldFormatter;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Field\Annotation\FieldFormatter;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * @FieldFormatter(
 *   id = "entity_operation",
 *   label = @Translation("Entity operation"),
 *   field_types = {
 *     "entity_operation"
 *   }
 * )
 */
class EntityOperationFieldFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#markup' => $this->t('%operation %entity_type of type %bundle', [
          '%operation' => $item->operation,
          '%entity_type' => $item->entity_type,
          '%bundle' => $item->bundle,
        ])
      ];
    }
    return $elements;
  }

}
