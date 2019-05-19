<?php

/**
 * @file
 * Contains virtual_entity.page.inc.
 *
 * Page callback for Virtual entity entities.
 */

use Drupal\Core\Render\Element;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Prepares variables for Virtual entity templates.
 *
 * Default template: virtual_entity.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 *
 * @usage $virtual_entity = $variables['elements']['#virtual_entity'];
 */
function template_preprocess_virtual_entity(array &$variables) {
  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}

/**
 * Prepares variables for a custom entity type creation list templates.
 *
 * Default template: virtual_entity-content-add-list.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - content: An array of virtual_entity-types.
 *
 * @see block_content_add_page()
 */
function template_preprocess_virtual_entity_content_add_list(array &$variables) {
  $variables['types'] = [];
  $query = \Drupal::request()->query->all();
  foreach ($variables['content'] as $type) {
    $variables['types'][$type->id()] = [
      'link' => Link::fromTextAndUrl($type->label(), new Url('entity.virtual_entity.add_form', [
        'virtual_entity_type' => $type->id(),
      ], ['query' => $query])),
      'description' => [
        '#markup' => $type->label(),
      ],
      'title' => $type->label(),
      'localized_options' => [
        'query' => $query,
      ],
    ];
  }
}
