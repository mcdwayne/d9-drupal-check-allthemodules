<?php

namespace Drupal\persian_fields\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'iran_postal_code_widget' widget.
 *
 * @FieldWidget(
 *   id = "iran_postal_code_widget",
 *   label = @Translation("Iran postal code"),
 *   field_types = {
 *     "iran_postal_code"
 *   }
 * )
 */
class IranPostalCodeWidget extends BasePersianWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['value'] = $element + [
        '#type' => 'textfield',
        '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
        '#size' => 10,
        '#placeholder' => '9052905090',
        '#maxlength' => 10,
        '#attributes' => [
          'class' => ['iran_postal_code'],
        ],
      ];

    return $element;
  }

}
