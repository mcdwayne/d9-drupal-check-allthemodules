<?php

namespace Drupal\commerce_currencies_price\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'commerce_currencies_price_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "commerce_currencies_price_formatter",
 *   module = "commerce_currencies_price",
 *   label = @Translation("Commerce price currencies"),
 *   field_types = {
 *     "commerce_currencies_price"
 *   }
 * )
 */
class CurrenciesPriceFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    // Does not actually output anything for now.
    return [];
  }

}
