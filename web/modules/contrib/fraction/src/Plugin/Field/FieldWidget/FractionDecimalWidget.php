<?php

namespace Drupal\fraction\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'fraction_decimal' widget.
 *
 * @FieldWidget(
 *   id = "fraction_decimal",
 *   label = @Translation("Decimal"),
 *   field_types = {
 *     "fraction"
 *   }
 * )
 */
class FractionDecimalWidget extends FractionWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'precision' => 0,
      'auto_precision' => TRUE,
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    // Decimal precision.
    $elements['precision'] = array(
      '#type' => 'textfield',
      '#title' => t('Decimal precision'),
      '#description' => t('Specify the number of digits after the decimal place to display when converting the fraction to a decimal. When "Auto precision" is enabled, this value essentially becomes a minimum fallback precision.'),
      '#default_value' => $this->getSetting('precision'),
      '#required' => TRUE,
      '#weight' => 0,
    );

    // Auto precision.
    $elements['auto_precision'] = array(
      '#type' => 'checkbox',
      '#title' => t('Auto precision'),
      '#description' => t('Automatically determine the maximum precision if the fraction has a base-10 denominator. For example, 1/100 would have a precision of 2, 1/1000 would have a precision of 3, etc.'),
      '#default_value' => $this->getSetting('auto_precision'),
      '#weight' => 1,
    );

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();

    // Summarize the precision setting.
    $precision = $this->getSetting('precision');
    $auto_precision = !empty($this->getSetting('auto_precision')) ? 'On' : 'Off';
    $summary[] = t('Precision: @precision, Auto-precision: @auto_precision', array(
      '@precision' => $precision,
      '@auto_precision' => $auto_precision,
    ));

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    // Pass the element through the parent's formElement method.
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    // Hide the numerator and denominator fields.
    $element['numerator']['#type'] = 'hidden';
    $element['denominator']['#type'] = 'hidden';

    // Load the precision setting.
    $precision = $this->getSetting('precision');

    // Add a 'decimal' textfield for capturing the decimal value.
    // The default value is converted to a decimal with the specified precision.
    $auto_precision = !empty($this->getSetting('auto_precision')) ? TRUE : FALSE;
    $element['decimal'] = array(
      '#type' => 'textfield',
      '#default_value' => $items[$delta]->fraction->toDecimal($precision, $auto_precision),
      '#size' => 15,
    );

    // Add decimal validation. This is also where we will convert the decimal
    // to a fraction.
    $element['#element_validate'][] = array($this, 'validateDecimal');

    return $element;
  }

  /**
   * Form element validation handler for $this->formElement().
   */
  public function validateDecimal(&$element, &$form_state, $form) {

    // Convert the value to a fraction.
    $fraction = fraction_from_decimal($element['decimal']['#value']);

    // Get the numerator and denominator.
    $numerator = $fraction->getNumerator();
    $denominator = $fraction->getDenominator();

    // Set the numerator and denominator values for the form.
    $values = array(
      'decimal' => $element['decimal']['#value'],
      'numerator' => $numerator,
      'denominator' => $denominator,
    );
    $form_state->setValueForElement($element, $values);

    // Only continue with validation if the value is not empty.
    if (empty($element['decimal']['#value'])) {
      return;
    }

    // The maximum number of digits after the decimal place is 9.
    // Explicitly perform a string comparison to ensure precision.
    if ((string) $denominator > '1000000000') {
      $form_state->setError($element, t('The maximum number of digits after the decimal place is 9.'));
    }

    // Ensure that the decimal value is within an acceptable value range.
    // Convert the fraction back to a decimal, because that is what will be
    // stored. Explicitly perform a string comparison to ensure precision.
    $decimal = (string) $fraction->toDecimal(0, TRUE);
    $min_decimal = (string) fraction('-9223372036854775808', $denominator)->toDecimal(0, TRUE);
    $max_decimal = (string) fraction('9223372036854775807', $denominator)->toDecimal(0, TRUE);
    $scale = strlen($denominator) - 1;
    $in_bounds = $this->checkInBounds($decimal, $min_decimal, $max_decimal, $scale);
    if (!$in_bounds) {
      $form_state->setError($element, t('The number you entered is outside the range of acceptable values. This limitation is related to the decimal precision, so reducing the precision may solve the problem.'));
    }
  }

  /**
   * Helper method to check if a given value is in between two other values,
   * using BCMath and strings for arbitrary-precision operations where possible.
   *
   * @param string $value
   *   The value to check.
   * @param string $min
   *   The minimum bound.
   * @param string $max
   *   The maximum bound.
   * @param int $scale
   *   Optional scale integer to pass into bcsub() if BCMath is used.
   *
   * @return bool
   *   Returns TRUE if $number is between $min and $max, FALSE otherwise.
   */
  protected function checkInBounds($value, $min, $max, $scale = 0) {

    // If BCMath isn't available, let PHP handle it via normal float comparison.
    if (!function_exists('bcsub')) {
      return ($value > $max || $value < $min) ? FALSE : TRUE;
    }

    // Subtract the minimum bound and maximum bounds from the value.
    $diff_min = bcsub($value, $min, $scale);
    $diff_max = bcsub($value, $max, $scale);

    // If either have a difference of zero, then the value is in bounds.
    if ($diff_min == 0 || $diff_max == 0) {
      return TRUE;
    }

    // If the first character of $diff_min is a negative sign (-), then the
    // value is less than the minimum, and therefore out of bounds.
    if (substr($diff_min, 0, 1) == '-') {
      return FALSE;
    }

    // If the first character of $diff_max is a number, then the value is
    // greater than the maximum, and therefore out of bounds.
    if (is_numeric(substr($diff_max, 0, 1))) {
      return FALSE;
    }

    // Assume the value is in bounds if none of the above said otherwise.
    return TRUE;
  }
}
