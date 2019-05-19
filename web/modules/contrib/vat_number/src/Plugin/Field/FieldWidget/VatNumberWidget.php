<?php

namespace Drupal\vat_number\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\vat_number\Controller\VatNumberController;

/**
 * Plugin implementation of the 'vat_widget' widget.
 *
 * @FieldWidget(
 *   id = "vat_widget",
 *   module = "vat_number",
 *   label = @Translation("VAT Number"),
 *   field_types = {
 *     "vat_number"
 *   }
 * )
 */
class VatNumberWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element += [
      '#type' => 'textfield',
      '#default_value' => isset($items[$delta]->getValue()['value']) ? $items[$delta]->getValue()['value'] : NULL,
      '#element_validate' => [
        [$this, 'validate'],
      ],
    ];

    return ['value' => $element];
  }

  /**
   * Validate the fields and check if the vat number is valid.
   */
  public function validate($element, FormStateInterface $form_state) {
    $value = $element['#value'];
    if (!empty($value)) {

      // Include the VAT Controller.
      $vatController = new VatNumberController($value);

      // Validate VAT number.
      $valid = $vatController->check();
      if (!$valid['status']) {
        $form_state->setError($element, $valid['message']);
      }
    }
  }

}
