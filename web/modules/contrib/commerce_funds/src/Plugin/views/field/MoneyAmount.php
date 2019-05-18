<?php

namespace Drupal\commerce_funds\Plugin\views\field;

use Drupal\views\Plugin\views\field\NumericField;
use Drupal\views\ResultRow;

/**
 * Field handler to provide amount.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("commerce_funds_amount")
 */
class MoneyAmount extends NumericField {

  /**
   * {@inheritdoc}
   */
  public function preRender(&$values) {
    foreach ($values as $key => $row) {
      if ($row->_entity->bundle() !== 'conversion') {
        $currency = \Drupal::service('commerce_funds.transaction_manager')->getTransactionCurrency($row->_entity->id());
        $values[$key]->transaction_currency = $currency;
        $currency_symbol = $currency->getSymbol();
      }
      else {
        $currency_symbol = '';
      }

      $values[$key]->transaction_currency_symbol = $currency_symbol;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);

    return $values->transaction_currency_symbol . $value;
  }

}
