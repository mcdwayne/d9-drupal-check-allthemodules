<?php

namespace Drupal\field_group_modal_bootstrap\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\RenderElement;

/**
 * Provides a render element for a html element.
 *
 * @FormElement("field_group_modal_bootstrap")
 */
class ModalElement extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);

    return [
      '#process' => [
        [$class, 'processModalElement'],
      ],
      '#theme_wrappers' => ['field_group_modal_bootstrap'],
    ];
  }

  /**
   * Process a html element.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   details element.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The processed element.
   */
  public static function processModalElement(array &$element, FormStateInterface $form_state) {

    $element['#attached']['library'][] = 'field_group_modal_bootstrap/bootstrap';

    return $element;
  }

}
