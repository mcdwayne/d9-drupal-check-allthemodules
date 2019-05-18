<?php
/**
 * @file
 *   Footermap theme and preprocess implementation.
 */

use Drupal\Core\Template\Attribute;

/**
 * Footermap template preprocess function.
 * @param &$variables
 */
function template_preprocess_footermap(&$variables) {
  $variables['attributes'] = new Attribute($variables['attributes']);
}

/**
 * Template Preprocess Footermap Item.
 *
 * @param &$variables
 *   An associative array with the following keys:
 *     - title: The menu link title.
 *     - url: The menu link url object.
 *     - attributes: An array of attributes to apply to the list item element.
 *     - children: (optional) Array of menu children.
 */
function template_preprocess_footermap_item(&$variables) {
  $variables['attributes'] = new Attribute($variables['attributes']);
  $variables['has_children'] = !empty($variables['children']);

  if ($variables['has_children']) {
    uasort($variables['children'], ['\Drupal\Component\Utility\SortArray', 'sortByWeightProperty']);
  }
}

/**
 * Template Preprocess Footermap Header
 *
 * @param &$variables
 *   An associative array with the following keys:
 *     - title: The title of the menu.
 *     - title_display: If set to "hidden", title will not be displayed.
 *     - items: An array of footermap items.
 *     - attributes: An associative array of attributes to apply to the item
 *       list.
 *     - menu_name: The menu machine name.
 */
function template_preprocess_footermap_header(&$variables) {
  $title_classes = array('footermap-col-heading', 'footermap-col-heading--' . $variables['menu_name']);

  // Add visually-hidden class when title is hidden.
  if (isset($variables['title_display']) && $variables['title_display'] == 'hidden') {
    $title_classes[] = 'visually-hidden';
  }
  uasort($variables['items'], ['\Drupal\Component\Utility\SortArray', 'sortByWeightProperty']);

  $variables['title_attributes'] = new Attribute(array('class' => $title_classes));
  $variables['attributes'] = new Attribute($variables['attributes']);
}
