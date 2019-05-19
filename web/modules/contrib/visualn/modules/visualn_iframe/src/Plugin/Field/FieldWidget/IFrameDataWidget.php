<?php

namespace Drupal\visualn_iframe\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'visualn_iframe_data' widget.
 *
 * @FieldWidget(
 *   id = "visualn_iframe_data",
 *   label = @Translation("VisualN IFrame data"),
 *   field_types = {
 *     "visualn_iframe_data"
 *   }
 * )
 */
class IFrameDataWidget extends WidgetBase {

  // @todo: The TextareaWidget, StringTextareaWidget widgets have excessive properties so use the base class


  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['value'] = $element + [
      '#type' => 'textarea',
      '#default_value' => $items[$delta]->value,
      '#rows' => $this
        ->getSetting('rows'),
      '#placeholder' => $this
        ->getSetting('placeholder'),
      '#attributes' => [
        'class' => [
          'js-text-full',
          'text-full',
        ],
      ],
      // disable editing
      // @todo: or even hide the textarea at all
      '#disabled' => TRUE,
    ];
    return $element;
  }

}
