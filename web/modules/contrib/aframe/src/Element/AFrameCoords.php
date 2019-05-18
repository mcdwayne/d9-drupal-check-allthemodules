<?php

namespace Drupal\aframe\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;

/**
 * @FormElement("aframe_coords")
 */
class AFrameCoords extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input'          => TRUE,
      '#pre_render'     => [
        [$class, 'preRenderAFrameCoords'],
      ],
      '#process'        => [
        [$class, 'processAFrameCoords'],
      ],
      '#theme'          => 'aframe_coords',
      '#theme_wrappers' => ['form_element'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input !== FALSE && $input !== NULL) {
      // This should be a string, but allow other scalars since they might be
      // valid input in programmatic form submissions.
      if (!is_scalar($input)) {
        $input = '';
      }
      return str_replace(["\r", "\n"], '', $input);
    }
    return NULL;
  }

  /**
   *
   */
  public static function preRenderAFrameCoords($element) {
    static::setAttributes($element, [
      'form-aframe-coords',
      'form--inline',
      'clearfix',
    ]);

    return $element;
  }

  /**
   *
   */
  public static function processAFrameCoords($element) {
    $element['x'] = [
      '#type'          => 'textfield',
      '#title'         => 'X',
      '#size'          => 5,
      '#default_value' => $element['#default_value']['x'],
    ];

    $element['y'] = [
      '#type'          => 'textfield',
      '#title'         => 'Y',
      '#size'          => 5,
      '#default_value' => $element['#default_value']['y'],
    ];

    $element['z'] = [
      '#type'          => 'textfield',
      '#title'         => 'Z',
      '#size'          => 5,
      '#default_value' => $element['#default_value']['z'],
    ];

    return $element;
  }

}
