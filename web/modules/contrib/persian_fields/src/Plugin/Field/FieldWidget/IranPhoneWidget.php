<?php

namespace Drupal\persian_fields\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'iran_phone_widget' widget.
 *
 * @FieldWidget(
 *   id = "iran_phone_widget",
 *   label = @Translation("Iran phone"),
 *   field_types = {
 *     "iran_phone"
 *   }
 * )
 */
class IranPhoneWidget extends BasePersianWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['value'] = $element + [
        '#type' => 'textfield',
        '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
        '#size' => 13,
        '#placeholder' => '021 8888 8888',
        '#maxlength' => 13,
        '#attributes' => [
          'class' => ['iran_phone'],
        ],
      ];

    return $element;
  }

}
