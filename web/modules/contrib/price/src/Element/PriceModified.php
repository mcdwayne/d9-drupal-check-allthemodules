<?php

namespace Drupal\price\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provides a price form element.
 *
 * Usage example:
 * @code
 * $form['amount'] = [
 *   '#type' => 'price_modified',
 *   '#title' => $this->t('Amount'),
 *   '#default_value' => ['number' => '99.99', 'currency_code' => 'USD', 'modifier' => 'from'],
 *   '#allow_negative' => FALSE,
 *   '#size' => 60,
 *   '#maxlength' => 128,
 *   '#required' => TRUE,
 *   '#available_currencies' => ['USD', 'EUR'],
 *   '#available_modifiers' => ['from', 'best_offer'],
 * ];
 * @endcode
 *
 * @FormElement("price_modified")
 */
class PriceModified extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      // List of currencies codes. If empty, all currencies will be available.
      '#available_currencies' => [],
      '#available_modifiers' => [],

      '#size' => 10,
      '#maxlength' => 128,
      '#default_value' => NULL,
      '#allow_negative' => FALSE,
      '#attached' => [
        'library' => ['price/admin'],
      ],
      '#process' => [
        [$class, 'processElement'],
        [$class, 'processAjaxForm'],
        [$class, 'processGroup'],
      ],
      '#pre_render' => [
        [$class, 'preRenderGroup'],
      ],
      '#input' => TRUE,
      '#theme_wrappers' => ['container'],
    ];
  }

  /**
   * Builds the price_price form element.
   *
   * @param array $element
   *   The initial price_price form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The built price_price form element.
   *
   * @throws \InvalidArgumentException
   *   Thrown when #default_value is not an instance of
   *   \Drupal\price\Price.
   */
  public static function processElement(array $element, FormStateInterface $form_state, array &$complete_form) {
    $default_value = $element['#default_value'];
    if (isset($default_value) && !self::validateDefaultValue($default_value)) {
      throw new \InvalidArgumentException('The #default_value for a price_modified element must be an array with "number", "currency_code" and "modifier" keys.');
    }

    /** @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $currency_storage */
    $currency_storage = \Drupal::service('entity_type.manager')->getStorage('price_currency');
    /** @var \Drupal\price\Entity\CurrencyInterface[] $currencies */
    $currencies = $currency_storage->loadMultiple();
    $currency_codes = array_keys($currencies);
    // Keep only available currencies.
    $available_currencies = $element['#available_currencies'];
    if (isset($available_currencies) && !empty($available_currencies)) {
      $currency_codes = array_intersect($currency_codes, $available_currencies);
    }
    // Stop rendering if there are no currencies available.
    if (empty($currency_codes)) {
      return $element;
    }
    $fraction_digits = [];
    foreach ($currencies as $currency) {
      $fraction_digits[] = $currency->getFractionDigits();
    }

    /** @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $modifier_storage */
    $modifier_storage = \Drupal::service('entity_type.manager')->getStorage('price_modifier');
    /** @var \Drupal\price\Entity\CurrencyInterface[] $currencies */
    $modifiers = $modifier_storage->loadMultiple();

    $modifier_codes = array_keys($modifiers);
    // Keep only available modifiers.
    $available_modifiers = $element['#available_modifiers'];
    if (isset($available_modifiers) && !empty($available_modifiers)) {
      $modifier_codes = array_intersect($modifier_codes, $available_modifiers);
    }
    // Stop rendering if there are no modifiers available.
    if (empty($modifier_codes)) {
      return $element;
    }

    $modifier_options = [];
    foreach ($modifier_codes as $modifier_code) {
      $modifier_options[$modifier_code] = $modifiers[$modifier_code]->label();
    }

    $element['#tree'] = TRUE;
    $element['#attributes']['class'][] = 'form-type-price-modified';

    $element['modifier'] = [
      '#type' => 'select',
      '#title' => t('Modifier'),
      '#default_value' => $default_value ? $default_value['modifier'] : NULL,
      '#options' => $modifier_options,
    ];

    $element['number'] = [
      '#type' => 'price_number',
      '#title' => $element['#title'],
      '#default_value' => $default_value ? $default_value['number'] : NULL,
      '#required' => $element['#required'],
      '#size' => $element['#size'],
      '#maxlength' => $element['#maxlength'],
      '#min_fraction_digits' => min($fraction_digits),
      // '6' is the field storage maximum.
      '#max_fraction_digits' => 6,
      '#min' => $element['#allow_negative'] ? NULL : 0,
    ];
    unset($element['#size']);
    unset($element['#maxlength']);

    if (count($currency_codes) == 1) {
      $last_visible_element = 'number';
      $currency_code = reset($currency_codes);
      $element['number']['#field_suffix'] = $currency_code;
      $element['currency_code'] = [
        '#type' => 'hidden',
        '#value' => $currency_code,
      ];
    }
    else {
      $last_visible_element = 'currency_code';
      $element['currency_code'] = [
        '#type' => 'select',
        '#title' => t('Currency'),
        '#default_value' => $default_value ? $default_value['currency_code'] : NULL,
        '#options' => array_combine($currency_codes, $currency_codes),
        '#title_display' => 'invisible',
        '#field_suffix' => '',
      ];
    }

    // Add the help text if specified.
    if (!empty($element['#description'])) {
      $element[$last_visible_element]['#field_suffix'] .= '<div class="description">' . $element['#description'] . '</div>';
    }

    return $element;
  }

  /**
   * Validates the default value.
   *
   * @param mixed $default_value
   *   The default value.
   *
   * @return bool
   *   TRUE if the default value is valid, FALSE otherwise.
   */
  public static function validateDefaultValue($default_value) {
    if (!is_array($default_value)) {
      return FALSE;
    }
    if (!array_key_exists('number', $default_value) || !array_key_exists('currency_code', $default_value) || !array_key_exists('modifier', $default_value)) {
      return FALSE;
    }
    return TRUE;
  }

}
