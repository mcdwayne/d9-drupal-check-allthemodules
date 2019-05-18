<?php

namespace Drupal\commerce_currencies_price\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provides a price per currency form element.
 *
 * Usage example:
 *
 * @code
 * $form['price'] = [
 *   '#type' => 'commerce_currencies_price',
 *   '#default_value' => [
 *     'prices' => [
 *       'USD' => ['number' => '0.00', 'currency_code' => 'USD'],
 *     ],
 *   ],
 * ];
 * @endcode
 *
 * @FormElement("commerce_currencies_price")
 */
class CurrenciesPrice extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#multiple' => TRUE,
      '#input' => TRUE,
      '#tree' => TRUE,
      '#default_value' => [],
      '#process' => [
        [$class, 'processCurrenciesPrice'],
      ],
      '#element_validate' => [
        [$class, 'validateCurrenciesPrice'],
      ],
      '#theme_wrappers' => ['container'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function processCurrenciesPrice(array &$element, FormStateInterface $form_state, array &$complete_form) {

    // Process defaults.
    $defaultValue = $element['#default_value'];

    // Get enabled currencies.
    $enabled_currencies = self::enabledCurrencies();

    $element['prices'] = [
      '#tree' => TRUE,
      '#type' => 'details',
      '#title' => t('Price per currency'),
    ];

    foreach ($enabled_currencies as $key => $currency) {
      $element['prices'][$key] = [
        '#type' => 'commerce_price',
        '#title' => t('@currency price', ['@currency' => $key]),
        '#title_display' => 'before',
        '#default_value' => [
          'number' => $defaultValue['prices'][$key]['number'] ?? '',
          'currency_code' => $key,
        ],
        '#required' => $element['#required_prices'],
        '#size' => 10,
        '#available_currencies' => [$key],
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function validateCurrenciesPrice(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $value = $form_state->getValue($element['#parents']);
    if (!empty($value['prices'])) {
      $form_state->setValueForElement($element, $value['prices']);
    }
  }

  /**
   * Get enabled currencies.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   List of all enabled currencies.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function enabledCurrencies() {
    static $enabled;

    if (!isset($enabled)) {
      $enabled = \Drupal::EntityTypeManager()
        ->getStorage('commerce_currency')
        ->loadByProperties([
          'status' => TRUE,
        ]);

    }
    return $enabled;
  }

}
