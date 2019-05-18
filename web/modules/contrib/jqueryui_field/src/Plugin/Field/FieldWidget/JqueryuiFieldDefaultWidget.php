<?php

namespace Drupal\jqueryui_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'jqueryui_field_default' widget.
 *
 * @FieldWidget(
 *   id = "jqueryui_field_accordion",
 *   label = @Translation("Jqueryui Field"),
 *   field_types = {
 *     "jqueryui_field"
 *   }
 * )
 */
class JqueryuiFieldDefaultWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $element += [
      '#type' => 'fieldset',
      '#title' => $this->t('Content'),
    ];

    $element['label'] = [
      '#title' => $this->t('Label'),
      '#type' => 'textfield',
      '#default_value' => isset($items[$delta]->label) ? $items[$delta]->label : NULL,
    ];
    $element['description'] = [
      '#title' => $this->t('Description'),
      '#type' => 'text_format',
      '#format' => 'full_html',
      '#default_value' => isset($items[$delta]->description) ? $items[$delta]->description : NULL,
    ];

    return $element;
  }

  /**
   * Preserve Ritch Text Value.
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as $key => $value) {
      $values[$key]['description'] = $value['description']['value'];
    }
    return $values;
  }

}
