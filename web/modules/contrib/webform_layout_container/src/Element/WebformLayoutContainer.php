<?php

namespace Drupal\webform_layout_container\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Container;

/**
 * Provides a render element for webform layout container.
 *
 * @FormElement("layout_container")
 */
class WebformLayoutContainer extends Container {

  /**
   * {@inheritdoc}
   */
  public static function processContainer(&$element, FormStateInterface $form_state, &$complete_form) {
    $element = parent::processContainer($element, $form_state, $complete_form);
    $element['#attributes']['class'][] = 'webform-layoutcontainer';
    $element['#attributes']['class'][] = 'js-webform-layoutcontainer';
    if (isset($element['#align'])) {
      $element['#attributes']['class'][] = 'webform-layoutcontainer--' . $element['#align'];
    }
    else {
      $element['#attributes']['class'][] = 'webform-layoutcontainer--equal';
    }
    $element['#attached']['library'][] = 'webform_layout_container/webform.element.layoutcontainer';
    return $element;
  }

}
