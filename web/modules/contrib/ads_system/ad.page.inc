<?php

/**
 * @file
 * Contains ad.page.inc.
 *
 * Page callback for Ad entities.
 */

use Drupal\Core\Render\Element;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Prepares variables for Ad templates.
 *
 * Default template: ad.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_ad(array &$variables) {
  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}

/**
 * Prepares variables for a custom entity type creation list templates.
 *
 * Default template: ad-content-add-list.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - content: An array of ad-types.
 *
 * @see block_content_add_page()
 */
function template_preprocess_ad_content_add_list(array &$variables) {
  $variables['types'] = [];
  $query = \Drupal::request()->query->all();
  foreach ($variables['content'] as $type) {
    $variables['types'][$type->id()] = [
      'link' => Link::fromTextAndUrl($type->label(), new Url('entity.ad.add_form', [
        'ad_type' => $type->id(),
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
