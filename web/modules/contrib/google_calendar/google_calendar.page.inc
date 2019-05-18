<?php

/**
 * @file
 * Contains google_calendar.page.inc.
 *
 * Page callback for Google Calendar entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Google Calendar templates.
 *
 * Default template: google_calendar.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_google_calendar(array &$variables) {
  // Fetch GoogleCalendarEntity Entity Object.
  $google_calendar = $variables['elements']['#google_calendar'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
