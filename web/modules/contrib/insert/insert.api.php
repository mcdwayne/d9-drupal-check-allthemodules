<?php
/**
 * @file
 * This file documents hooks provided by the Insert module.
 *
 * None of this code is executed by the Insert module, it is provided here for
 * reference as an example how to implement these hooks in your own module.
 */

/**
 * Returns an array of widget plugin ids, keyed by insert type.
 * @see insert_insert_widgets()
 *
 * @example
 * return ['text' => ['string_textfield', 'some_other_plugin_id']]
 *
 *
 * @return string[]
 */
function hook_insert_widgets() {
  return [];
}

/**
 * Returns a list of styles to be added to the list of styles available to
 * Insert.
 * @see insert_insert_styles()
 *
 * @example
 * return $insertType === 'text'
 *   ? ['insert_text__plain' => ['label' => t('Plain')]] : [];
 *
 * @param string $insertType
 * @return array
 *   Schema: [<string> style_name => ['label' => <string>, 'weight' => <int>]]
 *   or for image styles: [<string> style_name => <ImageStyle>]
 */
function hook_insert_styles($insertType) {
  return [];
}

/**
 * Triggered before Insert module processing. Returning FALSE skips processing.
 * Returning a string indicates Insert's parent process callback that $element
 * contains multiple sub-values under the specified key, so, instead of $element
 * itself, that sub-array will be processed.
 * Variables to be passed on to hook_insert_variables may be added to $element.
 * @see insert_insert_process()
 *
 * @example
 * if ($insertType === 'text') {
 *   $element['#insert'][$insertType]['custom_variable'] = 'value';
 *   $element['#attached']['library'][] = 'insert_text/insert_text';
 * }
 *
 * @param string $insertType
 * @param array $element
 * @return array
 *   [] or [FALSE] or ['<value elements key>']
 */
function hook_insert_process(&$insertType, array &$element) {
  return [];
}

/**
 * Allows adding/altering variables to be passed to a style's template.
 * Returning FALSE skips the style from appearing in the Insert style select
 * box.
 * @see insert_insert_variables()
 *
 * @example
 * if ($insertType === 'text') {
 *   return ['insert_text_bold' => $element['#insert']['settings']['insert_text_bold']];
 * }
 *
 * @param string $insertType
 * @param array $element
 * @param string $styleName
 * @param array $vars
 * @return array
 *   [] or [FALSE]
 */
function hook_insert_variables($insertType, array &$element, $styleName, &$vars) {
  return [];
}

/**
 * May return a rendered template to be preferred over native Insert module
 * templates.
 * @see insert_insert_render()
 *
 * @example
 * $moduleName = explode('__', $styleName, 2)[0];
 * return $moduleName === 'insert_text'
 *   ? \Drupal::theme()->render(
 *       ['insert_text__' . str_replace('-', '_', $styleName), 'insert_text'],
 *       $vars
 *     )
 *   : [];
 *
 * @param string $styleName
 * @param array $vars
 * @return array
 */
function hook_insert_render($styleName, array $vars) {
  return [];
}
