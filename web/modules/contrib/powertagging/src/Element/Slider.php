<?php
/**
 * @file
 * Contains \Drupal\powertagging\Element\Slider.
 */

namespace Drupal\powertagging\Element;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provides a slider element.
 *
 * @FormElement("slider")
 */
class Slider extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return array(
      '#input' => TRUE,
      '#theme' => 'slider',
      '#title' => NULL,
      '#process' => [
        [$class, 'processSlider'],
      ],
      '#pre_render' => [
        [$class, 'preRenderSlider'],
      ],
      '#animate' => 'fast',
      '#min' => 1,
      '#max' => 100,
      '#orientation' => 'horizontal',
      '#range' => 'min',
      '#step' => 1,
      '#default_value' => NULL,
      '#slider_style' => NULL,
      '#slider_length' => NULL,
    );
  }

  /**
   * Prepare the render array for the template.
   *
   * @param array $element
   *   The form element.
   *
   * @return array
   *   The form element.
   */
  public static function preRenderSlider(array $element) {
    $element['slider'] = [
      '#type' => 'textfield',
      '#attributes' => [
        'name' => $element['#name'] . '_value',
        'id' => $element['#id'] . '-value',
        'readonly' => 'readonly',
      ],
      '#required' => $element['#required'],
      '#value' => $element['#value'],
      '#attached' => [
        'library' => [
          'powertagging/slider',
        ],
        'drupalSettings' => [
          'powertagging_slider' => [
            $element['#id'] => [
              'animate' => $element['#animate'],
              'min' => $element['#min'] * 1,
              'max' => $element['#max'] * 1,
              'orientation' => $element['#orientation'],
              'range' => $element['#range'],
              'step' => $element['#step'] * 1,
            ],
          ],
        ],
      ],
    ];
    $element['#default_value'] = ['slider'];

    return $element;
  }

  /**
   * Processes transfer slider.
   *
   * @param array $element
   *   The form element whose value is being processed.
   * @param FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The form element whose value has been processed.
   */
  public static function processSlider(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $element['#tree'] = TRUE;
    $inputs = $form_state->getUserInput();
    $name = $element['#name'];

    if (isset($inputs["{$name}_value"])) {
      $element['#value'] = ['slider' => $inputs["{$name}_value"]];
      $form_state->setValue($name, $element['#value']);
    }

    if (!is_null($element['#slider_length'])) {
      if ($element['#orientation']) {
        $element['#slider_length'] = "width: {$element['#slider_length']};";
      }
      else {
        $element['#slider_length'] = "height : {$element['#slider_length']};";
      }
    }

    return $element;
  }

}