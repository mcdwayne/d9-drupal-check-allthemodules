<?php

namespace Drupal\fraction\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;

/**
 * Format fraction as percentage.
 *
 * @FieldFormatter(
 *   id = "fraction_percentage",
 *   label = @Translation("Percentage"),
 *   field_types = {
 *     "fraction"
 *   }
 * )
 */
class PercentageFormatter extends FractionDecimalFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();

    // Iterate through the items.
    foreach ($items as $delta => $item) {
      $percentage = clone $item->fraction;
      $percentage->multiply(fraction_from_decimal('100'));

      $auto_precision = !empty($this->getSetting('auto_precision'));
      $elements[$delta] = array(
        '#markup' => $percentage->toDecimal($this->getSetting('precision'), $auto_precision) . '%',
      );
    }

    return $elements;
  }

}
