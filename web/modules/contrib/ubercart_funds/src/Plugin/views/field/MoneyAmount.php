<?php

namespace Drupal\ubercart_funds\Plugin\views\field;

use Drupal\views\Plugin\views\field\NumericField;
use Drupal\views\ResultRow;

/**
 * Field handler to provide amount / 100.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("uc_funds_amount")
 */
class MoneyAmount extends NumericField {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);

    if (is_null($value)) {
      return '';
    }

    return uc_currency_format($value / 100);
  }

}
