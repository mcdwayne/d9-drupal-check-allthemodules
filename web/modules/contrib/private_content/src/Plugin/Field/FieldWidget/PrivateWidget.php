<?php

namespace Drupal\private_content\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation for PrivateWidget.
 *
 * @FieldWidget(
 *   id = "private",
 *   label = @Translation("Private"),
 *   field_types = {
 *     "private",
 *   }
 * )
 */
class PrivateWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['stored'] = $element + [
      '#type' => 'checkbox',
      '#default_value' => $items[0]->value,
    ];

    // Override the title display from the incoming $element.
    // @todo Not sure why we need this but BooleanCheckboxWidget doesn't need it.
    $element['stored']['#title_display'] = 'after';

    return $element;
  }

}
