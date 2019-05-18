<?php

namespace Drupal\radiostoslider\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Radios;

/**
 * Provides a radios element with the radios-to-slide jQuery plugin.
 *
 * @FormElement("radios_to_slider")
 */
class RadiosToSliderElement extends Radios {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#process' => [
        [$class, 'processRadios'],
      ],
      '#theme_wrappers' => ['radios_to_slider'],
      '#pre_render' => [
        [$class, 'preRenderCompositeFormElement'],
      ],
    ];
  }

  /**
   * Expands a radios element into individual radio elements.
   */
  public static function processRadios(
    &$element,
    FormStateInterface $form_state,
    &$complete_form) {

    $element = parent::processRadios($element, $form_state, $complete_form);
    // Add module library.
    $element['#attached']['library'][] = 'radiostoslider/default';
    return $element;
  }

}
