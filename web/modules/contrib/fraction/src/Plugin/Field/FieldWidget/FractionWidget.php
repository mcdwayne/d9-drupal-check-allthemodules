<?php

namespace Drupal\fraction\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'fraction' widget.
 *
 * @FieldWidget(
 *   id = "fraction",
 *   label = @Translation("Fraction"),
 *   field_types = {
 *     "fraction"
 *   }
 * )
 */
class FractionWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $element['#type'] = 'fieldset';

    $element['numerator'] = array(
      '#type' => 'textfield',
      '#title' => t('Numerator'),
      '#default_value' => isset($items[$delta]->numerator) ? $items[$delta]->numerator : NULL,
    );

    $element['denominator'] = array(
      '#type' => 'textfield',
      '#title' => t('Denominator'),
      '#default_value' => isset($items[$delta]->denominator) ? $items[$delta]->denominator : NULL,
    );

    // Add validation.
    $element['#element_validate'][] = array($this, 'validateFraction');

    return $element;
  }

  /**
   * Form element validation handler for $this->formElement().
   *
   * Validate the fraction.
   */
  public function validateFraction(&$element, &$form_state, $form) {

    // If the denominator is empty, but the numerator isn't, print an error.
    if (empty($element['denominator']['#value']) && !empty($element['numerator']['#value'])) {
      $form_state->setError($element, t('The denominator of a fraction cannot be zero or empty (if a numerator is provided).'));
    }

    // Numerators must be between -9223372036854775808 and 9223372036854775807.
    // Explicitly perform a string comparison to ensure precision.
    if (!empty($element['numerator']['#value']) && ((string) $element['numerator']['#value'] < '-9223372036854775808' || (string) $element['numerator']['#value'] > '9223372036854775807')) {
      $form_state->setError($element, t('The numerator of a fraction must be between -9223372036854775808 and 9223372036854775807.'));
    }

    // Denominators must be between 0 and 4294967295.
    // Explicitly perform a string comparison to ensure precision.
    if (!empty($element['denominator']['#value']) && ((string) $element['denominator']['#value'] < '0' || (string) $element['denominator']['#value'] > '4294967295')) {
      $form_state->setError($element, t('The denominator of a fraction must be between 0 and 4294967295.'));
    }
  }
}
