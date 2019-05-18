<?php

/**
 * @file
 * Contains site.page.inc.
 *
 * Page callback for Site entities.
 */

use Drupal\Core\Render\Element;
use Drupal\views\Views;
use Drupal\Core\Url;

/**
 * Prepares variables for Site templates.
 *
 * Default template: site.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_site(array &$variables) {
  /** @var \Drupal\micro_site\Entity\SiteInterface $site */
  $site = $variables['elements']['#site'];
  $view_mode = $variables['elements']['#view_mode'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }

  $variables['view_mode'] = $view_mode;
  $variables['site'] = $site;
  $variables['site_name'] = $site->getName();
  $variables['site_path'] = $site->getSitePath();
  $variables['site_url'] = $site->getSiteUrl();
}
