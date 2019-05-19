<?php

/**
 * @file
 * Contains trance.page.inc..
 *
 * Page callback for trance entities.
 */

use Drupal\Core\Render\Element;
use Drupal\Core\Url;

/**
 * Prepares variables for trance templates.
 *
 * Default template: trance.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 *   - content: helpful content variable for templates.
 */
function template_preprocess_trance(array &$variables) {
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}

/**
 * Prepares variables for custom entity type creation list templates.
 *
 * Default template: trance-content-add-list.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - content: An array of trance content entity types.
 *
 * @see block_content_add_page()
 */
function template_preprocess_trance_content_add_list(&$variables) {
  $variables['types'] = [];
  $query = \Drupal::request()->query->all();
  foreach ($variables['content'] as $type) {
    $url = new Url('entity.' . $type->getEntityType()->getBundleOf() . '.add_form', [
      $type->getEntityType()->id() => $type->id(),
    ], ['query' => $query]);
    $variables['types'][$type->id()] = [
      'url' => $url->toString(),
      'label' => $type->label(),
      'description' => $type->getDescription(),
    ];
  }
}
