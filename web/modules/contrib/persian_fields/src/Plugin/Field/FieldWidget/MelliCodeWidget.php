<?php

namespace Drupal\persian_fields\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'melli_code_widget' widget.
 *
 * @FieldWidget(
 *   id = "melli_code_widget",
 *   label = @Translation("Melli code"),
 *   field_types = {
 *     "melli_code"
 *   }
 * )
 */
class MelliCodeWidget extends BasePersianWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['value'] = $element + [
        '#type' => 'textfield',
        '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
        '#size' => 10,
        '#placeholder' => '1360838007',
        '#maxlength' => 10,
        '#attributes' => [
          'class' => ['melli_code'],
        ],
      ];
    return $element;
  }

}
