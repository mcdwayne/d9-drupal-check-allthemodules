<?php

namespace Drupal\entity_serial\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'entity_serial_widget' widget.
 *
 * @FieldWidget(
 *   id = "entity_serial_widget",
 *   label = @Translation("Entity serial"),
 *   field_types = {
 *     "entity_serial_field_type"
 *   }
 * )
 */
class EntitySerialWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['value'] = [
      // Understand number (integer)
      '#type' => 'hidden',
      // @todo this value is a dummy one and should even not exist
      // because the field barely displays a computed result
      '#default_value' => 0,
    ];
    return $element;
  }

}
