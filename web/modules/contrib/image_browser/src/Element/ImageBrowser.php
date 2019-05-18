<?php

namespace Drupal\image_browser\Element;

use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provides a form element for choosing a color.
 *
 * Properties:
 * - #default_value: Default value, in a format like #ffffff.
 *
 * Example usage:
 * @code
 * $form['background_image'] = array(
 *   '#type' => 'image_browser',
 *   '#title' => $this->t('Background Image'),
 *   '#default_value' => 'file:100',
 * );
 * @endcode
 *
 * @FormElement("image_browser")
 */
class ImageBrowser extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return array(
      '#input' => TRUE,
      '#process' => array(
        array($class, 'processAjaxForm'),
      ),
      '#pre_render' => array(
        array($class, 'preRenderField'),
      ),
      '#theme' => 'image_browser',
      '#theme_wrappers' => array('form_element'),
    );
  }

  /**
   * Prepares a #type 'image_browser' render element for input.html.twig.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   *   Properties used: #title, #value, #description, #attributes.
   *
   * @return array
   *   The $element with prepared variables ready for input.html.twig.
   */
  public static function preRenderField($element) {
    $element['#attributes']['type'] = 'hidden';
    $element['#attached']['library'][] = 'image_browser/image_browser';
    Element::setAttributes($element, array('id', 'name', 'value'));
    static::setAttributes($element, array('form-image-browser'));

    return $element;
  }

}