<?php

namespace Drupal\multiple_selects\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsSelectWidget;
use Drupal\Core\Field\WidgetInterface;

/**
 * Plugin implementation of the 'options_select' widget.
 *
 * @FieldWidget(
 *   id = "multiple_options_select",
 *   label = @Translation("Multiple select list(s)"),
 *   field_types = {
 *     "entity_reference",
 *     "list_integer",
 *     "list_float",
 *     "list_string"
 *   },
 * )
 */
class OptionsMultipleSelectWidget extends OptionsSelectWidget implements WidgetInterface {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element[$this->column] = $element;
    $element[$this->column]['#default_value'] = empty($items[$delta]->{$this->column}) ? '_none' : $items[$delta]->{$this->column};
    $element[$this->column]['#multiple'] = FALSE;
    unset($element['#type']);

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function validateElement(array $element, FormStateInterface $form_state) {
    if ($element['#required'] && $element['#value'] === '_none') {
      $form_state->setError($element, t('@name field is required.', array('@name' => $element['#title'])));
    }

    if (isset($element['#value']) && $element['#value'] === '_none') {
      $form_state->setValueForElement($element, NULL);
    }
  }

}
