<?php

namespace Drupal\norwegian_id\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\norwegian_id\Plugin\Field\FieldType\NorwegianIdItem;

/**
 * Plugin implementation of the 'simple_norwegian_personal_id' widget.
 *
 * @FieldWidget(
 *   id = "norwegian_id_textfield",
 *   label = @Translation("Simple Textfield input for Norwegian personal ID"),
 *   field_types = {
 *     "norwegian_id"
 *   }
 * )
 */
class NorwegianIdTextfieldWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['value'] = $element + [
      '#type'          => 'textfield',
      '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
      '#size'          => NorwegianIdItem::ID_LENGTH,
      '#maxlength'     => NorwegianIdItem::ID_LENGTH,
    ];

    return $element;
  }

}
