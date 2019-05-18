<?php

namespace Drupal\field_tooltips_data\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'field_tooltips_data' formatter.
 *
 * @FieldFormatter(
 *   id = "field_tooltips_data_formatter",
 *   label = @Translation("Tooltips"),
 *   field_types = {
 *     "field_tooltips_data"
 *   }
 * )
 */
class TooltipsDataDefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = array();
    foreach ($items as $delta => $item) {
      $element[$delta] = array(
        '#type' => 'hidden',
        '#value' => strip_tags($item->value),
      );
      $element['#attached']['library'][] = 'field_tooltips_data/tooltips-data-view';
      $element['#attached']['drupalSettings']['imageTooltip']['icon'] = '/' . drupal_get_path('module', 'field_tooltips_data') . '/images/druplicon-small.png';
    }
    return $element;
  }

}
