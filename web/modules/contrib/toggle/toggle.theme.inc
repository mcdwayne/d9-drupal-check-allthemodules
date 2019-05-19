<?php

/**
 * @file
 * Theme hooks for Toggle.
 */

use Drupal\Core\Template\Attribute;

/**
 * Returns HTML for a "toggle_radios" element.
 *
 * @param $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties of the element.
 *     Properties used: #title, #value, #options, #description, #required,
 *     #attributes, #children.
 *
 * @return string
 *   The rendered HTML.
 *
 * @ingroup themeable
 * @see toggle_theme()
 */
function theme_toggle_radios($variables) {
  $element = $variables['element'];
  $attributes = array();
  if (isset($element['#id'])) {
    $attributes['id'] = $element['#id'];
  }
  $attributes['class'] = 'form-toggle-radios';
  if (!empty($element['#attributes']['class'])) {
    $attributes['class'] .= ' ' . implode(' ', $element['#attributes']['class']);
  }
  if (isset($element['#attributes']['title'])) {
    $attributes['title'] = $element['#attributes']['title'];
  }
  return '<div' . new Attribute($attributes) . '>' . (!empty($element['#children']) ? $element['#children'] : '') . '</div>';
}

/**
 * Returns HTML for a "toggle_radio" form element.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties of the element.
 *     Properties used: #attributes.
 *
 * @return string
 *   The rendered HTML.
 *
 * @ingroup themeable
 *
 * @see toggle_theme()
 */
function theme_toggle_radio($variables) {
  $element = $variables['element'];
  $attributes = $variables['attributes'];
  return '<input' . $attributes . ' />' . drupal_render_children($element);
}

/**
 * Returns HTML for a "toggle_checkboxes" element.
 *
 * @param $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties of the element.
 *     Properties used: #title, #value, #options, #description, #required,
 *     #attributes, #children.
 *
 * @return string
 *   The rendered HTML.
 *
 * @ingroup themeable
 * @see toggle_theme()
 */
function theme_toggle_checkboxes($variables) {
  $element = $variables['element'];
  $attributes = array();
  if (isset($element['#id'])) {
    $attributes['id'] = $element['#id'];
  }
  $attributes['class'][] = 'form-toggle-checkboxes';
  if (!empty($element['#attributes']['class'])) {
    $attributes['class'] = array_merge($attributes['class'], $element['#attributes']['class']);
  }
  if (isset($element['#attributes']['title'])) {
    $attributes['title'] = $element['#attributes']['title'];
  }
  return '<div' . new Attribute($attributes) . '>' . (!empty($element['#children']) ? $element['#children'] : '') . '</div>';
}

/**
 * Returns HTML for a "toggle_checkbox" form element.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties of the element.
 *     Properties used: #attributes.
 *
 * @return string
 *   The rendered HTML.
 *
 * @ingroup themeable
 *
 * @see toggle_theme()
 */
function theme_toggle_checkbox($variables) {
  $element = $variables['element'];
  $attributes = $variables['attributes'];
  $return = '';

  if (!array_key_exists('#multiple', $element) || !$element['#multiple']) {
    $return .= '<label>' . $element['#title'] . '</label>';
  }

  $return .= '<input' . $attributes . ' />' . drupal_render_children($element);
  return $return;
}

/**
 * Preprocesses variables for theme_toggle_radio().
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties of the element.
 *
 * @ingroup themeable
 */
function template_preprocess_toggle_radio(&$variables) {
  $element = $variables['element'];
  $variables['attributes'] = new Attribute($element['#attributes']);
  // Trick Drupal into attaching our library by adding it as a child element,
  // which causes the attachment to get processed by drupal_render_children().
  // This is necessary because we don't use drupal_render() in our theme function.
  $variables['element']['library']['#attached']['library'][] = 'toggle/drupal.toggle';
}

/**
 * Preprocesses variables for theme_toggle_checkbox().
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties of the element.
 *
 * @ingroup themeable
 */
function template_preprocess_toggle_checkbox(&$variables) {
  $element = $variables['element'];
  $variables['attributes'] = new Attribute($element['#attributes']);
  // Trick Drupal into attaching our library by adding it as a child element,
  // which causes the attachment to get processed by drupal_render_children().
  // This is necessary because we don't use drupal_render() in our theme function.
  $variables['element']['library']['#attached']['library'][] = 'toggle/drupal.toggle';
}
