<?php

namespace Drupal\commerce_quantity_pricing\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'commerce_quantity_pricing_quantity' widget.
 *
 * @FieldWidget(
 *   id = "commerce_quantity_pricing_quantity",
 *   label = @Translation("Quantity Pricing"),
 *   field_types = {
 *     "quantity_pricing"
 *   }
 * )
 */
class QuantityPricingWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $value = $items[$delta]->getValue() !== NULL ? $items[$delta]->getValue() : '';

    $element += [
      '#type' => 'value',
      '#element_validate' => [
        [$this, 'validate'],
      ],
      '#default_value' => $value,
    ];

    $price = [
      'number' => 0,
      'currency_code' => '',
    ];
    if ($value && isset($value['price'])) {
      $price = explode('/', $value['price']);
      $price = [
        'number' => $price[0],
        'currency_code' => $price[1],
      ];
    }

    $element['price'] = [
      '#type' => 'commerce_price',
      '#available_currencies' => ['USD', 'CAD'],
      '#title' => t('Price'),
      '#default_value' => $price,
    ];

    $int_elements = ['min', 'max', 'step'];
    foreach ($int_elements as $int_element) {
      $element[$int_element] = [
        '#type' => 'number',
        '#title' => t('@type value', ['@type' => ucfirst($int_element)]),
        '#default_value' => $value[$int_element] ?? 0,
      ];
    }

    return ['value' => $element];
  }

  /**
   * {@inheritdoc}
   */
  public function validate($element, FormStateInterface $form_state) {
    $values = $form_state->getValues()['field_quantity_pricing'][$element['#delta']]['value'];
    $values['price'] = $values['price']['number'] . '/' . $values['price']['currency_code'];
    $form_state->setValueForElement($element, $values);
  }

}
