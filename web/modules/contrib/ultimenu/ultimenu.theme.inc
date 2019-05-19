<?php

/**
 * @file
 * Preprocessors and theme functions of Ultimenu module.
 */

use Drupal\Core\Template\Attribute;

/**
 * Prepares variables for ultimenu templates.
 *
 * Default template: ultimenu.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties of the element.
 *     Properties used: #config, #items, #plugin_id, #attributes.
 *     - config: The available config related to this particular instance.
 *     - items: An array of available items.
 *     - attributes: An associative array of attributes to be placed in the UL
 *       tag.
 *
 * @see ultimenu.html.twig
 */
function template_preprocess_ultimenu(array &$variables) {
  $element = $variables['element'];
  foreach (['config', 'items'] as $key) {
    $variables[$key] = isset($element["#$key"]) ? $element["#$key"] : [];
  }

  $config = $variables['config'];
  $variables['goodies'] = $goodies = ultimenu()->getSetting('goodies');
  $variables['attributes'] = new Attribute();
  $variables['attributes']['id'] = 'ultimenu-' . $config['menu_name'];
  $variables['attributes']['data-ultimenu'] = $config['menu_name'];

  if (!empty($config['ajaxify'])) {
    $variables['attributes']['data-ultiajax'] = TRUE;
  }

  $classes = isset($variables['attributes']['class']) ? $variables['attributes']['class'] : [];
  if ($config['menu_name'] == 'main') {
    // Makes Ultimenu hoverable if off-canvas is not enabled for all devices.
    if (empty($goodies['off-canvas-all'])) {
      $classes[] = 'ultimenu--hover';
    }
  }
  else {
    // Other Ultimenu is always hoverable for desktop.
    $classes[] = 'ultimenu--hover';
  }

  $variables['attributes']['class'] = array_merge(['ultimenu'], $classes);
}
