<?php

namespace Drupal\field_widget\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Adds a readonly field widget.
 *
 * @todo Expand this to boolean, ER etc. fields.
 *
 * @FieldWidget(
 *   id = "readonly",
 *   label = @Translation("Readonly text field"),
 *   field_types = {
 *     "string",
 *     "integer",
 *   },
 * )
 */
class Readonly extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $property_name = $items[0]->mainPropertyName();

    $element[$property_name] = [
      '#type' => 'textfield',
      '#disabled' => TRUE,
      '#value' => $items[0]->{$property_name},
    ];

    return $element;
  }

}
