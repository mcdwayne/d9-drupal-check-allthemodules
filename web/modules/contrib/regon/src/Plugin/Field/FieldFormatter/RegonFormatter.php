<?php
/**
 * Author: Remigiusz Kornaga <remkor@o2.pl>
 */

namespace Drupal\regon\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'regon' formatter.
 *
 * @FieldFormatter(
 *   id = "regon",
 *   module = "regon",
 *   label = @Translation("REGON"),
 *   field_types = {
 *     "regon"
 *   }
 * )
 */
class RegonFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();

    foreach ($items as $delta => $item) {
      $elements[$delta] = array(
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#value' => $item->number,
      );
    }

    return $elements;
  }

}
