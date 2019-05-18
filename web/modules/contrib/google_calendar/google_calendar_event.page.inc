<?php

/**
 * @file
 * Contains google_calendar_event.page.inc.
 *
 * Page callback for Google Calendar Event entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Google Calendar Event templates.
 *
 * Default template: google_calendar_event.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_google_calendar_event(array &$variables) {
  // Fetch GoogleCalendarEvent Entity Object.
  $google_calendar_event = $variables['elements']['#google_calendar_event'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
