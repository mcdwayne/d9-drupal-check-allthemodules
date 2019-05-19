<?php

namespace Drupal\uuid_extra\Plugin\field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'uuid' widget.
 *
 * @FieldWidget(
 *   id = "uuid",
 *   label = @Translation("UUID"),
 *   field_types = {
 *     "uuid"
 *   }
 * )
 */
class UuidFieldWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(
    FieldItemListInterface $items,
    $delta,
    array $element,
    array &$form,
    FormStateInterface $form_state
  ) {
    $element['value'] = $element + [
      '#type' => 'textfield',
      '#disabled' => TRUE,
      '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
      '#process' => [[static::class, 'enableAccess']],
    ];


    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function form(FieldItemListInterface $items, array &$form, FormStateInterface $form_state, $get_delta = NULL) {
    $element = parent::form($items, $form, $form_state, $get_delta);
    $element['#process'] = [[static::class, 'enableAccess']];

    return $element;
  }

  public static function enableAccess(&$element) {
    $element['#access'] = TRUE;
    return $element;
  }

}
