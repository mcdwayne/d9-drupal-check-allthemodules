<?php

namespace Drupal\picker\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsSelectWidget;

/**
 * Plugin implementation of the 'picker_widget' widget.
 *
 * @FieldWidget(
 *   id = "picker_widget",
 *   label = @Translation("picker widget"),
 *   field_types = {
 *     "list_integer",
 *     "list_float",
 *     "list_string",
 *     "entity_reference"
 *   },
 *   multiple_values = TRUE
 * )
 */
class PickerWidget extends OptionsSelectWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'size' => 60,
      'placeholder' => '',
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $element += array(
      '#picker' => 1,
    );
    return $element;
  }

}
