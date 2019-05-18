<?php

/**
 * @file
 * Contains theme hooks for contacts_events.
 */

use Drupal\Core\Link;
use Drupal\Core\Render\Element;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * Implements hook_theme().
 */
function contacts_events_theme() {
  return [
    'contacts_event' => [
      'render element' => 'elements',
      'template' => 'contacts_event',
    ],
    'contacts_event__listing' => [
      'base hook' => 'contacts_event',
      'template' => 'contacts_event--listing',
    ],
    'event_content_add_list' => [
      'render element' => 'content',
      'variables' => ['content' => NULL],
    ],
    'contacts_ticket' => [
      'render element' => 'elements',
      'template' => 'contacts_ticket',
    ],
    'contacts_ticket_content_add_list' => [
      'render element' => 'content',
      'variables' => ['content' => NULL],
    ],
    'contacts_events_checkout_progress' => [
      'variables' => [
        'steps' => [],
      ],
    ],
    'contacts_events_ticket_summary' => [
      'variables' => [
        'order_entity' => NULL,
        'checkout_step' => '',
      ],
    ],
    'contacts_events_booking_summary' => [
      'variables' => [
        'order_entity' => NULL,
        'tickets' => NULL,
        'manage_tickets_url' => NULL,
        'booking_total' => NULL,
        'amount_paid' => NULL,
        'balance' => NULL,
      ],
    ],
  ];
}

/**
 * Implements hook_theme_suggestions_HOOK().
 */
function contacts_events_theme_suggestions_contacts_event(array $variables) {
  $suggestions = [];
  $entity = $variables['elements']['#contacts_event'];
  $sanitized_view_mode = strtr($variables['elements']['#view_mode'], '.', '_');

  $suggestions[] = 'contacts_event__' . $sanitized_view_mode;
  $suggestions[] = 'contacts_event__' . $entity->bundle();
  $suggestions[] = 'contacts_event__' . $entity->bundle() . '__' . $sanitized_view_mode;
  $suggestions[] = 'contacts_event__' . $entity->id();
  $suggestions[] = 'contacts_event__' . $entity->id() . '__' . $sanitized_view_mode;
  return $suggestions;
}

/**
 * Prepares variables for Event templates.
 *
 * Default template: contacts_event.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_contacts_event(array &$variables) {
  // Fetch Event Entity Object.
  /* @var \Drupal\contacts_events\Entity\Event $contacts_event */
  $contacts_event = $variables['elements']['#contacts_event'];

  $variables['link'] = $contacts_event->toUrl()->toString();
  $variables['links']['more_info'] = $contacts_event->toLink('More info', 'canonical')->toRenderable();
  $variables['links']['more_info']['#access'] = $contacts_event->access('view');

  // Generate the render array for the Book Now link but assume no access.
  $variables['links']['book'] = $contacts_event->toLink('Book now', 'book')->toRenderable();
  $variables['#access'] = FALSE;

  // Only show the Book Now link if the user has access or is anonymous.
  // Anonymous users will be redirected to the login URL with a destination to
  // bring them back to the booking form.
  if ($contacts_event->isBookingOpen()) {
    $booking_access = $contacts_event->access('book');
    $anonymous_user = Drupal::currentUser()->isAnonymous();
    if ($booking_access || $anonymous_user) {
      $variables['#access'] = TRUE;
    }
  }

  try {
    $event_listing_route_name = 'view.contacts_events_listings.page_1';
    // If the route doesn't exist this will throw a RouteNotFoundException
    // whereas createFromRoute will error without exception.
    \Drupal::service('router.route_provider')->getRouteByName($event_listing_route_name);
    $variables['links']['listing'] = Link::createFromRoute(new TranslatableMarkup('Back to events'), $event_listing_route_name, [])->toRenderable();
  }
  catch (RouteNotFoundException $exception) {
  }

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}

/**
 * Implements hook_theme_suggestions_HOOK().
 */
function contacts_events_theme_suggestions_contacts_ticket(array $variables) {
  $suggestions = [];
  $entity = $variables['elements']['#contacts_ticket'];
  $sanitized_view_mode = strtr($variables['elements']['#view_mode'], '.', '_');

  $suggestions[] = 'contacts_ticket__' . $sanitized_view_mode;
  $suggestions[] = 'contacts_ticket__' . $entity->bundle();
  $suggestions[] = 'contacts_ticket__' . $entity->bundle() . '__' . $sanitized_view_mode;
  $suggestions[] = 'contacts_ticket__' . $entity->id();
  $suggestions[] = 'contacts_ticket__' . $entity->id() . '__' . $sanitized_view_mode;
  return $suggestions;
}

/**
 * Prepares variables for Ticket templates.
 *
 * Default template: contacts_ticket.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_contacts_ticket(array &$variables) {
  // Fetch Ticket Entity Object.
  $contacts_ticket = $variables['elements']['#contacts_ticket'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
