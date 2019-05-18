<?php

/**
 * @file
 * Contains box.page.inc.
 *
 * Page callback for Box entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Box templates.
 *
 * Default template: box.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the box data and any
 *     fields attached to the box.
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_box(array &$variables) {
  $variables['show_title'] = in_array($variables['elements']['#view_mode'], ['default', 'full']);
  $variables['label'] = !empty($variables['elements']['title']) ? $variables['elements']['title'] : '';
  unset($variables['elements']['title']);

  $variables['box'] = $variables['elements']['#box'];
  /** @var \Drupal\box\Entity\BoxInterface $box */
  $box = $variables['box'];

  $variables['url'] = $box->toUrl()->toString();

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
