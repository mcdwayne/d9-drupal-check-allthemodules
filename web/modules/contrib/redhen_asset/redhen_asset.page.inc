<?php

/**
 * @file
 * Contains redhen_asset.page.inc..
 *
 * Page callback for Asset entities.
 */

use Drupal\Core\Render\Element;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Prepares variables for Asset templates.
 *
 * Default template: redhen_asset.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_redhen_asset(array &$variables) {
  // Fetch Asset Entity Object.
  $redhen_asset = $variables['elements']['#redhen_asset'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}

/**
* Prepares variables for a custom entity type creation list templates.
*
* Default template: redhen_asset-content-add-list.html.twig.
*
* @param array $variables
*   An associative array containing:
*   - content: An array of redhen_asset-types.
*
* @see block_content_add_page()
*/
function template_preprocess_redhen_asset_content_add_list(&$variables) {
  $variables['types'] = array();
  $query = \Drupal::request()->query->all();
  foreach ($variables['content'] as $type) {
    $variables['types'][$type->id()] = array(
      'link' => Link::fromTextAndUrl($type->label(), new Url('entity.redhen_asset.add_form', array(
        'redhen_asset_type' => $type->id()
      ), array('query' => $query))),
      'description' => array(
      '#markup' => $type->label(),
      ),
      'title' => $type->label(),
      'localized_options' => array(
      'query' => $query,
      ),
    );
  }
}
