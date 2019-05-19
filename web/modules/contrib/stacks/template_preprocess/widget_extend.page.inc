<?php

/**
 * @file
 * Contains widget_extend.page.inc.
 *
 * Page callback for Widget Extend entities.
 */

use Drupal\Core\Render\Element;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Prepares variables for Widget Extend templates.
 *
 * Default template: widget_extend.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_widget_extend(array &$variables) {
  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}

/**
 * Prepares variables for a custom entity type creation list templates.
 *
 * Default template: widget_extend-content-add-list.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - content: An array of widget_extend-types.
 *
 * @see block_content_add_page()
 */
function template_preprocess_widget_extend_content_add_list(&$variables) {
  $variables['types'] = [];
  $query = \Drupal::request()->query->all();
  foreach ($variables['content'] as $type) {
    $variables['types'][$type->id()] = [
      'link' => Link::fromTextAndUrl($type->label(), new Url('entity.widget_extend.add_form', [
        'widget_extend_type' => $type->id(),
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
