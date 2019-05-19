<?php

/**
 * @file
 * Contains sapi_data.page.inc.
 *
 * Page callback for Statistics API Data entry entities.
 */

use Drupal\Core\Render\Element;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Prepares variables for Statistics API Data entry templates.
 *
 * Default template: sapi_data.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_sapi_data(array &$variables) {
  // Fetch SAPIData Entity Object.
  $sapi_data = $variables['elements']['#sapi_data'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}

/**
* Prepares variables for a custom entity type creation list templates.
*
* Default template: sapi_data-content-add-list.html.twig.
*
* @param array $variables
*   An associative array containing:
*   - content: An array of sapi_data-types.
*
* @see block_content_add_page()
*/
function template_preprocess_sapi_data_content_add_list(&$variables) {
  $variables['types'] = array();
  $query = \Drupal::request()->query->all();
  foreach ($variables['content'] as $type) {
    $variables['types'][$type->id()] = array(
      'link' => Link::fromTextAndUrl($type->label(), new Url('entity.sapi_data.add_form', array(
        'sapi_data_type' => $type->id()
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
