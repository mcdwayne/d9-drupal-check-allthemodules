<?php

namespace Drupal\number_double\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\NumberWidget;

/**
 * Plugin implementation of the 'number_double' widget.
 *
 * @FieldWidget(
 *   id = "number_double",
 *   label = @Translation("Number (double) field"),
 *   field_types = {
 *     "double"
 *   }
 * )
 */
class DoubleWidget extends NumberWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $parent = parent::formElement($items, $delta, $element, $form, $form_state);
    $element = $parent['value'];

    $element['#step'] = 'any';

    return array('value' => $element);
  }

}
