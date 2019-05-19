<?php

namespace Drupal\sketchfab\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'sketchfab_field' widget.
 *
 * @FieldWidget(
 *   id = "sketchfab_widget",
 *   label = @Translation("URL of the target"),
 *   module = "sketchfab",
 *   field_types = {
 *     "sketchfab_field"
 *   }
 * )
 */
class SketchfabWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $value = isset($items[$delta]->value) ? $items[$delta]->value : '';
    $element += array(
      '#type' => 'url',
      '#default_value' => $value,
      '#element_validate' => array(
        array($this, 'validate'),
      ),
    );
    return array('value' => $element);
  }

  /**
   * Validate the color text field.
   */
  public function validate($element, FormStateInterface $form_state) {
    $value = $element['#value'];
    if (strlen($value) == 0) {
      $form_state->setValueForElement($element, '');
      return;
    }
  }

}
