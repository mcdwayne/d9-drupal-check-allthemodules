<?php
namespace Drupal\qr_code_field_formatter\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'qr_code' widget.
 *
 * @FieldWidget(
 *   id = "qr_code_widget",
 *   module = "qr_code_field_formatter",
 *   label = @Translation("Render text as a QR Code"),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class QRCodeWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $value = isset($items[$delta]->value) ? $items[$delta]->value : '';
    $element += [
      '#type' => 'textfield',
      '#default_value' => $value,
      '#size' => 7,
      '#maxlength' => 7,
      '#element_validate' => [
        [static::class, 'validate'],
      ],
    ];
    return ['value' => $element];
  }

  /**
   * Validate the color text field.
   */
  public static function validate($element, FormStateInterface $form_state) {
    //Check that the number of bytes does not exceed the max for the specified
    //version number
  }

}

