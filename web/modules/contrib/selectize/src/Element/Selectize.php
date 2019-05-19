<?php

namespace Drupal\selectize\Element;

use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Render\Element;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a selectized form element.
 *
 * @FormElement("selectize")
 */
class Selectize extends FormElement {
  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);

    return array(
      '#input' => TRUE,
      '#multiple' => FALSE,
      '#autocomplete_route_name' => FALSE,
      '#process' => array(
        array($class, 'processSelectize'),
        array($class, 'processAjaxForm'),
      ),
      '#pre_render' => array(
        array($class, 'preRenderSelectize'),
      ),
      '#theme' => 'select',
      '#theme_wrappers' => array('form_element'),
      '#options' => array(),
      '#settings' => self::settings(),
    );
  }

  /**
   * Prepares a #type 'selectize' render element for input.html.twig.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   *   Properties used: #title, #value, #description, #size, #maxlength,
   *   #placeholder, #required, #attributes.
   *
   * @return array
   *   The $element with prepared variables ready for input.html.twig.
   */
  public static function preRenderSelectize($element) {
    Element::setAttributes($element, array('id', 'name', 'size'));
    static::setAttributes($element, array('form-select'));
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function processSelectize(&$element, FormStateInterface $form_state, &$complete_form) {
    if (isset($element['#settings'])) {
      $element['#attached']['drupalSettings']['selectize'][$element['#id']] = json_encode($element['#settings']);

      // if drag_drop plugin is requested, we need to load the sortable plugin.
      if (isset($element['#settings']['plugins']) && in_array('drag_drop', $element['#settings']['plugins'])) {
        $complete_form['#attached']['library'][] = 'core/jquery.ui.sortable';
      }

      // inject the selectize library and CSS assets.
      $complete_form['#attached']['library'][] = 'selectize/core';
      $complete_form['#attached']['library'][] = 'selectize/drupal';
    }

    // #multiple select fields need a special #name.
    if ($element['#multiple']) {
      $element['#attributes']['multiple'] = 'multiple';
      $element['#attributes']['name'] = $element['#name'] . '[]';
    }
    // A non-#multiple select needs special handling to prevent user agents from
    // preselecting the first option without intention. #multiple select lists do
    // not get an empty option, as it would not make sense, user interface-wise.
    else {
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
    }

    return $element;
  }

  /**
   * Return default settings for Selectize. Pass in values to override defaults.
   * @param $values
   * @return array
   */
  public static function settings(array $values = array()) {
    $settings = array(
      'create' => FALSE,
      'sortField' => 'text',
      'plugins' => NULL,
      'highlight' => TRUE,
      'maxItems' => 10,
      'delimiter' => NULL,
      'persist' => FALSE,
    );

    return array_merge($settings, $values);
  }

  /**
   * {@inheritdoc}
   */
  public static function preRenderAjaxForm($element) {
    // Add a reasonable default event handler if none was specified.
    if (isset($element['#ajax']) && !isset($element['#ajax']['event'])) {
      $element['#ajax']['event'] = 'change';
    }
    $element = parent::preRenderAjaxForm($element);
    return $element;
  }

}
