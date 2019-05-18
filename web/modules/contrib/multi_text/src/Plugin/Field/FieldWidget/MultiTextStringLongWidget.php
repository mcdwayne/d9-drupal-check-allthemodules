<?php

namespace Drupal\multi_text\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'multi_text_string_long_widget' widget.
 *
 * @FieldWidget(
 *   id = "multi_text_string_long_widget",
 *   label = @Translation("Delimited Textarea"),
 *   field_types = {
 *     "string",
 *     "string_long"
 *   },
 *   multiple_values = TRUE
 * )
 */
class MultiTextStringLongWidget extends MultiTextStringWidget {
  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'delimiter' => '---',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $element['value']['#type'] = 'textarea';
    return $element;
  }
}
