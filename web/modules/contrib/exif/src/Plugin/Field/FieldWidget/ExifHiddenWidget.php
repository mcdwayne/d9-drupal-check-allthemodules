<?php

namespace Drupal\exif\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'exif_readonly' widget.
 *
 * @FieldWidget(
 *   id = "exif_hidden",
 *   label = @Translation("metadata from image (hidden in forms)"),
 *   description = @Translation("field content is calculated from image field
 *   in the same content type (field are hidden from forms)"), multiple_values
 *   = true, field_types = {
 *     "string",
 *     "string_long",
 *     "text",
 *     "text_with_summary",
 *     "text_long",
 *     "entity_reference",
 *     "date",
 *     "datetime",
 *     "datestamp"
 *   }
 * )
 */
class ExifHiddenWidget extends ExifFieldWidgetBase {

  /**
   * Implements callback of formElement.
   *
   * @param array $element
   *   A form element array containing basic properties for the widget.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $form
   *   The form structure where widgets are being attached to.
   *
   * @return array
   *   The form elements for a single widget for this field.
   */
  public static function process(array $element, FormStateInterface $form_state, array $form) {

    $element['tid'] = [
      '#type' => 'hidden',
      '#value' => '',
    ];
    $element['value'] = [
      '#type' => 'hidden',
      '#value' => '',
    ];
    $element['timezone'] = [
      '#type' => 'hidden',
      '#value' => '',
    ];
    $element['value2'] = [
      '#type' => 'hidden',
      '#value' => '',
    ];

    $element['display'] = [
      '#type' => 'hidden',
      '#value' => '',
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element += [
      '#type' => '#hidden',
      '#value' => '',
      '#process' => [[get_class($this), 'process']],
    ];
    return $element;
  }

}
