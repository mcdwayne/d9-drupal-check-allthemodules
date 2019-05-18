<?php

namespace Drupal\cpf;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Base class for 'Field widget' plugin implementations.
 *
 * @ingroup field_widget
 */
class CpfWidgetBase extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    return $element;
  }

  /**
   * Returns the element ID.
   *
   * @param array $element
   *   A form element array containing basic properties for the widget.
   *
   * @return string
   *   The element ID.
   */
  public function formElementId(array $element) {
    $field_name = $this->fieldDefinition->getName();
    $field_name = strtolower(str_replace('_', '-', $field_name));
    $element_id = 'edit-' . $field_name . '-' . $element['#delta'] . '-value';

    return $element_id;
  }

  /**
   * Form element validation handler for the 'cpf' element.
   *
   * Checks that, in a field with multiple entries, the same CPF numbers have
   * been entered.
   */
  public static function validateElement($element, FormStateInterface $form_state, $form) {
    $widget = $element['#element_validate'][0][0];
    $element_value = $element['#value'];

    if (empty($element_value)) {
      return;
    }

    $field_name = $element['#field_name'];
    $user_input = $form_state->getUserInput();
    $field_values = $user_input[$field_name];
    $field_values = array_filter(array_column($field_values, 'value'));
    $field_values = array_count_values($field_values);

    if ($field_values[$element_value] > 1) {
      $form_state->setError($element, $widget->t('You cannot enter the same CPF numbers.'));
      return;
    }
  }

}
