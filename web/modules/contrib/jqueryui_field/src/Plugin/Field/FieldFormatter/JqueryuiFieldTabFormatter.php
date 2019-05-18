<?php

namespace Drupal\jqueryui_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'jqueryui_field' formatter.
 *
 * @FieldFormatter(
 *   id = "jqueryui_field_tab",
 *   label = @Translation("Jqueryui Tabs"),
 *   field_types = {
 *     "jqueryui_field"
 *   }
 * )
 */
class JqueryuiFieldTabFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = $tbl = $tbc = [];
    foreach ($items as $delta => $item) {
      $tbl['tab_' . $delta] = $item->label;
      $tbc['tab_' . $delta] = $item->description;
    }

    $elements = [
      '#theme' => 'jqueryui_field',
      '#tab_label' => $tbl,
      '#tab_description' => $tbc,
    ];
    $elements['#attached']['library'][] = 'jqueryui_field/jquerui_field.render';
    return $elements;
  }

}
