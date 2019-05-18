<?php

namespace Drupal\commerce_price_table\Plugin\Field\FieldWidget;

use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'commerce_price_table' widget.
 *
 * @FieldWidget(
 *   id = "commerce_price_table_multiple",
 *   label = @Translation("Price table"),
 *   field_types = {
 *     "commerce_price_table"
 *   }
 * )
 */
class PriceTableDefaultWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $currencies = \Drupal::entityTypeManager()->getStorage('commerce_currency')->loadMultiple();
    $currency_codes = array_keys($currencies);
    $default_currency_code = $this->getFieldSetting('currency_code');
    $element['#attributes']['class'][] = 'form-type-commerce-price';
    $element['amount'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Price'),
      '#default_value' => isset($items[$delta]->amount) ? $items[$delta]->amount : '',
      '#size' => 10,
      '#prefix' => '<div class="form-type-commerce-price-table">',
      '#suffix' => '</div>',
    ];
    $element['currency_code'] = [
      '#type' => 'select',
      '#title' => $this->t('Currency'),
      '#default_value' => $items[$delta]->currency_code ? $items[$delta]->currency_code : $default_currency_code,
      '#options' => array_combine($currency_codes, $currency_codes),
    ];
    $element['min_qty'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Minimum quantity'),
      '#default_value' => isset($items[$delta]->min_qty) ? $items[$delta]->min_qty : '',
      '#size' => 10,
      '#prefix' => '<div class="clear-commerce-price-table">',
      '#suffix' => '</div>'
    ];
    $element['max_qty'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Maximum quantity'),
      '#description' => $this->t('Use -1 for no upper limit.'),
      '#default_value' => isset($items[$delta]->max_qty) ? $items[$delta]->max_qty : '',
      '#size' => 10,
    ];
    $element['#attached']['library'][] = 'commerce_price_table/admin';
    $element['#element_validate'][] = [get_class($this), 'validateElement'];
    return $element;
  }

  /**
   * Form validation handler for widget elements.
   */
  public static function validateElement(array $element, FormStateInterface $form_state) {
    if ($element['amount']['#value'] !== '') {
      // Ensure the price is numeric.
      if (!is_numeric($element['amount']['#value'])) {
        $form_state->setError($element['amount'], t('%title: you must enter a numeric value for the price amount.', ['%title' => $element['amount']['#title']]));
      }
      else {
        $form_state->setValueForElement($element['amount'], $element['amount']['#value']);
      }

      // Ensure the quantity fields are valid values.
      if (!isset($element['min_qty']['#value']) || $element['min_qty']['#value'] == '' || !ctype_digit($element['min_qty']['#value']) || $element['min_qty']['#value'] < 1) {
        $form_state->setError($element['min_qty'], t('%name: Minimum quantity values must be integers greater than 0.', ['%name' => $element['min_qty']['#title']]));
      }
      else {
        $form_state->setValueForElement($element['min_qty'], $element['min_qty']['#value']);
      }

      if (!isset($element['max_qty']['#value']) || $element['max_qty']['#value'] == '' || (!ctype_digit($element['max_qty']['#value']) && $element['max_qty']['#value'] <> -1) || $element['max_qty']['#value'] < -1 || $element['max_qty']['#value'] == 0) {
        $form_state->setError($element['max_qty'], t('%name: Maximum quantity values must be integers greater than 0 or -1 for unlimited.', ['%name' => $element['max_qty']['#title']]));
      }

      if ($element['max_qty']['#value'] < $element['min_qty']['#value'] && $element['max_qty']['#value'] <> -1) {
        $form_state->setError($element['max_qty'], t('%name: Maximum quantity values must be higher than their related minimum quantity values.', ['%name' => $element['max_qty']['#title']]));
      }
      else {
        $form_state->setValueForElement($element['max_qty'], $element['max_qty']['#value']);
      }
    }
  }
}
