<?php

namespace Drupal\persian_fields\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'iran_mobile_widget' widget.
 *
 * @FieldWidget(
 *   id = "iran_mobile_widget",
 *   label = @Translation("Iran mobile"),
 *   field_types = {
 *     "iran_mobile"
 *   }
 * )
 */
class IranMobileWidget extends BasePersianWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['value'] = $element + [
        '#type' => 'textfield',
        '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
        '#size' => 13,
        '#placeholder' => '0912 222 2222',
        '#maxlength' => 13,
        '#attributes' => [
          'class' => ['iran_mobile'],
        ],
      ];
    return $element;
  }

}
