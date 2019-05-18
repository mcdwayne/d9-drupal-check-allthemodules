<?php

namespace Drupal\persian_fields\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'sheba_widget' widget.
 *
 * @FieldWidget(
 *   id = "sheba_widget",
 *   label = @Translation("Sheba"),
 *   field_types = {
 *     "sheba"
 *   }
 * )
 */
class ShebaWidget extends BasePersianWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['value'] = $element + [
        '#type' => 'textfield',
        '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
        '#size' => 26,
        '#placeholder' => 'IR062960000000100324200001',
        '#maxlength' => 26,
        '#attributes' => [
          'class' => ['sheba'],
        ],
      ];

    return $element;
  }

}
