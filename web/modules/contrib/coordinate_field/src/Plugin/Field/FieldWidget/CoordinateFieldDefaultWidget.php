<?php

namespace Drupal\coordinate_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'coordinate_default' widget.
 *
 * @FieldWidget(
 *   id = "coordinate_default",
 *   module = "coordinate_field",
 *   label = @Translation("Coordinates"),
 *   field_types = {
 *     "coordinate_field"
 *   }
 * )
 */
class CoordinateFieldDefaultWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $xpos = isset($items[$delta]->xpos) ? $items[$delta]->xpos : '0';
    $ypos = isset($items[$delta]->ypos) ? $items[$delta]->ypos : '0';

    $element['xpos'] = [
      '#default_value' => $xpos,
      '#title' => $this->getFieldSetting('xpos'),
      '#type' => 'textfield',
    ];

    $element['ypos'] = [
      '#default_value' => $ypos,
      '#title' => $this->getFieldSetting('ypos'),
      '#type' => 'textfield',
    ];

    $element += array(
      '#type' => 'fieldset',
    );

    return $element;
  }

}
