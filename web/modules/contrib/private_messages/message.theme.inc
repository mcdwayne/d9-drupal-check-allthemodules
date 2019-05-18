<?php

/**
 * @file
 * Contains message.page.inc.
 *
 * Page callback for Message entities.
 */

use Drupal\Core\Link;
use Drupal\Core\Template\Attribute;

/**
 * Prepares variables for Message templates.
 *
 * Default template: message.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_message(array &$variables) {
  /** @var \Drupal\private_messages\Entity\MessageInterface $message */
  $message = $variables['elements']['#message'];

  $variables['user']    = $message->getOwner();
  $variables['created'] = \Drupal::service('date.formatter')
    ->format($message->getCreatedTime(), 'custom', 'Y.m.d');
  $variables['message'] = [
    '#markup' => $message->get('message')->value,
  ];

  $variables['block_link'] = '';

  $links_items = [];
  if (\Drupal::currentUser()->id() == $message->getOwnerId()) {
    $links_items[] = [
      '#wrapper_attributes' => [
        'class' => ['nav-item', 'small'],
      ],
      '#markup'             => Link::createFromRoute(t('Delete'),
        'entity.message.delete_form', ['message' => $message->id()], [
          'attributes' => ['class' => 'nav-link'],
        ])->toString(),
    ];
  }

  $variables['links'] = [
    '#theme'      => 'item_list',
    '#list_type'  => 'ul',
    '#attributes' => [
      'class' => ['nav', 'nav-inline', 'text-xs-right'],
    ],
    '#items'      => $links_items,
  ];

  $variables['author_attributes'] = new Attribute();
  $variables['author'] = user_view($message->getOwner(), 'micro');
}
