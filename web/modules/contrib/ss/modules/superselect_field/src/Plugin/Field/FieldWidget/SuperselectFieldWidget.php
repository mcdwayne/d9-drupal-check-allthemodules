<?php

namespace Drupal\superselect_field\Plugin\Field\FieldWidget;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsSelectWidget;

/**
 * Plugin implementation of the 'superselect_select' widget.
 *
 * @FieldWidget(
 *   id = "superselect_select",
 *   label = @Translation("Superselect"),
 *   field_types = {
 *     "list_integer",
 *     "list_float",
 *     "list_string",
 *     "entity_reference"
 *   },
 *   multiple_values = TRUE
 * )
 */
class SuperselectFieldWidget extends OptionsSelectWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element += [
      '#superselect' => 1,
    ];

    return $element;
  }

}
