<?php

namespace Drupal\persian_fields\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'iran_card_number_widget' widget.
 *
 * @FieldWidget(
 *   id = "iran_card_number_widget",
 *   label = @Translation("Iran card number"),
 *   field_types = {
 *     "iran_card_number"
 *   }
 * )
 */
class IranCardNumberWidget extends BasePersianWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['value'] = $element + [
        '#type' => 'textfield',
        '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
        '#size' => 19,
        '#placeholder' => '6104 3378 5644 8830',
        '#maxlength' => 19,
        '#attributes' => [
          'class' => ['iran_card_number'],
        ],
      ];

    return $element;
  }

}
