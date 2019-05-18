<?php

namespace Drupal\clockpicker\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;

/**
 * Form element for a time using the clockpicker JS widget.
 *
 * @FormElement("clockpicker")
 */
class Clockpicker extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#default_value' => NULL,
      '#process' => [
        [$class, 'processElement'],
      ],
      '#input' => TRUE,
      '#theme' => 'input__textfield',
      '#theme_wrappers' => ['form_element'],
    ];
  }

  /**
   * Builds the commerce_number form element.
   *
   * @param array $element
   *   The initial commerce_number form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The built commerce_number form element.
   */
  public static function processElement(array $element, FormStateInterface $form_state, array &$complete_form) {
    $element['#attached']['library'][] = 'clockpicker/clockpicker';
    $element['#attributes']['class'][] = 'clockpicker';

    return $element;
  }

}
