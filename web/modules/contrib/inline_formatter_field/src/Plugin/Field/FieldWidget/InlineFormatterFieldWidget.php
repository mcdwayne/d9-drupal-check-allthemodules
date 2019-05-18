<?php

namespace Drupal\inline_formatter_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'inline_formatter_field_widget' widget.
 *
 * @FieldWidget(
 *   id = "inline_formatter_field_widget",
 *   module = "inline_formatter_field",
 *   label = @Translation("Inline Formatter Field Widget"),
 *   field_types = {
 *     "inline_formatter_field"
 *   }
 * )
 */
class InlineFormatterFieldWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getLabel();
    $display_format = isset($items[$delta]->display_format) ? $items[$delta]->display_format : NULL;
    $label = $this->t('Render the format for') . ' ' . $field_name;

    $element['display_format'] = [
      '#type' => 'checkbox',
      '#title' => $label,
      '#default_value' => $display_format,
    ];

    return $element;
  }

}
