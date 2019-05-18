<?php

namespace Drupal\md_fontello\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Render\Element;

/**
 * Provides a one-line text field form element.
 *
 * Properties:
 * - #maxlength: Maximum number of characters of input allowed.
 *
 * Usage example:
 * @code
 * $form['icon'] = array(
 *   '#type' => 'mdicon',
 *   '#title' => $this->t('Subject'),
 *   '#default_value' => $icon_id,
 *   '#required' => TRUE,
 *   '#packages' => ['fa'],
 * );
 * @endcode
 *
 * @FormElement("mdicon")
 */
class MDIcon extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return array(
      '#input' => TRUE,
      '#process' => array(
        array($class, 'processIcon'),
        array($class, 'processAjaxForm'),
      ),
      '#pre_render' => array(
        array($class, 'preRenderIcon'),
      ),
      '#theme' => 'select',
      '#theme_wrappers' => array('form_element'),
      '#multiple' => FALSE,
      '#packages' => [],
    );
  }

  /**
   * @param array $element
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param array $complete_form
   * @return array
   */
  public static function processIcon(array &$element, FormStateInterface $form_state, array &$complete_form) {
    // For proper validation we need to override the type as a select field.
    $element['#type'] = 'select';
    $element['#options'] = [];

    // If the element is set to #required through #states, override the
    // element's #required setting.
    $required = isset($element['#states']['required']) ? TRUE : $element['#required'];
    // If the element is required and there is no #default_value, then add an
    // empty option that will fail validation, so that the user is required to
    // make a choice. Also, if there's a value for #empty_value or
    // #empty_option, then add an option that represents emptiness.
    if (($required && !isset($element['#default_value'])) || isset($element['#empty_value']) || isset($element['#empty_option'])) {
      $element += array(
        '#empty_value' => '',
        '#empty_option' => $required ? t('- Select -') : t('- None -'),
      );
      // The empty option is prepended to #options and purposively not merged
      // to prevent another option in #options mistakenly using the same value
      // as #empty_value.
      $empty_option = array($element['#empty_value'] => $element['#empty_option']);
      $element['#options'] = $empty_option + $element['#options'];
    }
    else {
      $element['#options'][''] = t('- None -');
    }
    $packages = isset($element['#packages']) ? $element['#packages'] : [];
    // Add icon packages as options.
    $fontello = \Drupal::service('md_fontello');
    $fonts = $fontello->getListFonts();
    foreach ($fonts as $index => $font) {
      $font_name = $font['name'];
      if (!empty($packages) && !in_array($font_name, array_values($packages))) {
        $font_name = FALSE;
      }
      if ($font_name) {
        $element['#options'][$font['title']] = $fontello->getOptionFont($font['name']);
        $element['#attached']['library'][] = 'md_fontello/md_fontello.' . $font['name'];
      }
    }

    $element['#attached']['library'][] = 'md_fontello/md_fontello.element';

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input !== FALSE) {
      if (isset($element['#empty_value']) && $input === (string) $element['#empty_value']) {
        return $element['#empty_value'];
      }
      else {
        return $input;
      }
    }
  }

  /**
   * Prepares a select render element.
   */
  public static function preRenderIcon($element) {
    Element::setAttributes($element, array('id', 'name', 'size'));
    static::setAttributes($element, array('form-md-icon'));
    return $element;
  }

}
