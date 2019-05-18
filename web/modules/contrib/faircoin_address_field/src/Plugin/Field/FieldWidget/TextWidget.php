<?php

/**
 * @file
 * Contains \Drupal\faircoin_address_field\Plugin\field\widget\TextWidget.
 */

namespace Drupal\faircoin_address_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\faircoin_address_field\FaircoinAddressFieldAddressValidator;
/**
 * Plugin implementation of the 'faircoin_address_field_simple_text' widget.
 *
 * @FieldWidget(
 *   id = "faircoin_address_field_simple_text",
 *   module = "faircoin_address_field",
 *   label = @Translation("FairCoin address in plain text"),
 *   field_types = {
 *     "faircoin_address"
 *   }
 * )
 */
class TextWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $value = isset($items[$delta]->value) ? $items[$delta]->value : '';
    $element += array(
      '#type' => 'textfield',
      '#default_value' => $value,
      // Allow a slightly larger size that the field length to allow for some
      // configurations where all characters won't fit in input field.
      '#size' => 35,
      '#maxlength' => 35,
      '#element_validate' => array(
        array($this, 'validate'),
      ),
    );
    return array('value' => $element);
  }

  /**
   * Validate the FairCoin address field.
   */
  public function validate($element, FormStateInterface $form_state) {
    $value = $element['#value'];
    if (strlen($value) == 0) {
      $form_state->setValueForElement($element, '');
      return;
    }
    $validator = FaircoinAddressFieldAddressValidator::checkAddress($value);
    if (!$validator) {
      $form_state->setError($element, t("This is not a valid faircoin address."));
    }
  }

}
