<?php

namespace Drupal\micon\Element;

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
 *   '#type' => 'micon',
 *   '#title' => $this->t('Subject'),
 *   '#default_value' => $icon_id,
 *   '#required' => TRUE,
 *   '#packages' => ['fa'],
 * );
 * @endcode
 *
 * @FormElement("micon")
 */
class Micon extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#process' => [
        [$class, 'processMicon'],
        [$class, 'processAjaxForm'],
      ],
      '#pre_render' => [
        [$class, 'preRenderMicon'],
      ],
      '#theme' => 'select',
      '#theme_wrappers' => ['form_element'],
      '#multiple' => FALSE,
      '#packages' => [],
    ];
  }

  /**
   * Processes an Micon icon form element.
   *
   * This process callback is mandatory for select fields, since all user agents
   * automatically preselect the first available option of single (non-multiple)
   * select lists.
   *
   * @param array $element
   *   The form element to process.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The processed element.
   *
   * @see _form_validate()
   */
  public static function processMicon(array &$element, FormStateInterface $form_state, array &$complete_form) {
    // For proper validation we need to override the type as a select field.
    $element['#type'] = 'select';
    $element['#attributes']['class'][] = 'js-hide';
    $element['#options'] = [];

    // If the element is set to #required through #states, override the
    // element's #required setting.
    $required = isset($element['#states']['required']) ? TRUE : $element['#required'];
    // If the element is required and there is no #default_value, then add an
    // empty option that will fail validation, so that the user is required to
    // make a choice. Also, if there's a value for #empty_value or
    // #empty_option, then add an option that represents emptiness.
    if (($required && !isset($element['#default_value'])) || isset($element['#empty_value']) || isset($element['#empty_option'])) {
      $element += [
        '#empty_value' => '',
        '#empty_option' => $required ? t('- Select -') : t('- None -'),
      ];
      // The empty option is prepended to #options and purposively not merged
      // to prevent another option in #options mistakenly using the same value
      // as #empty_value.
      $empty_option = [$element['#empty_value'] => $element['#empty_option']];
      $element['#options'] = $empty_option + $element['#options'];
    }
    else {
      $element['#options'][''] = t('- None -');
    }

    // Add icon packages as options.
    $packages = \Drupal::service('micon.icon.manager')->getIcons();
    $include = is_array($element['#packages']) ? array_filter($element['#packages']) : [];
    if (!empty($include)) {
      $packages = array_intersect_key($packages, $include);
    }
    foreach ($packages as $icons) {
      foreach ($icons as $icon) {
        if (count($packages) > 1) {
          $element['#options'][$icon->getPackageLabel()][$icon->getSelector()] = $icon->toJson();
        }
        else {
          $element['#options'][$icon->getSelector()] = $icon->toJson();
        }
      }
    }

    $element['#attached']['library'][] = 'micon/micon.element';

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
  public static function preRenderMicon($element) {
    Element::setAttributes($element, ['id', 'name', 'size']);
    static::setAttributes($element, ['form-micon']);
    return $element;
  }

}
