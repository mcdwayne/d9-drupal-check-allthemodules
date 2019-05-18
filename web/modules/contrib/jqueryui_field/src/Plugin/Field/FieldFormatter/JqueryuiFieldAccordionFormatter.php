<?php

namespace Drupal\jqueryui_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'jqueryui_field_accordion' formatter.
 *
 * @FieldFormatter(
 *   id = "jqueryui_field_accordion",
 *   label = @Translation("Jqueryui Accordion"),
 *   field_types = {
 *     "jqueryui_field"
 *   }
 * )
 */
class JqueryuiFieldAccordionFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = $accordion = [];
    foreach ($items as $delta => $item) {
      $accordion[$delta]['label'] = $item->label;
      $accordion[$delta]['description'] = $item->description;
    }

    $elements = [
      '#theme' => 'jqueryui_field',
      '#accordion' => $accordion,
    ];
    $elements['#attached']['library'][] = 'jqueryui_field/jquerui_field.render';
    return $elements;
  }

}
