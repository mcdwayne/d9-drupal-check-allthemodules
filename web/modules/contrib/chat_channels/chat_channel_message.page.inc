<?php

/**
 * @file
 * Contains chat_channel_message.page.inc.
 *
 * Page callback for Chat channel message entities.
 */
use Drupal\Core\Render\Element;

/**
 * Prepares variables for Chat channel message templates.
 *
 * Default template: chat_channel_message.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_chat_channel_message(array &$variables) {
  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }

  /** @var \Drupal\chat_channels\Entity\ChatChannelMessageInterface $chat_channel_message */
  $chat_channel_message = $variables['elements']['#chat_channel_message'];

  /** @var \Drupal\Core\Datetime\DateFormatterInterface $date_service */
  $date_service = \Drupal::service('date.formatter');
  $variables['content']['created'] = [
    '#type' => 'markup',
    '#markup' => $date_service->format($chat_channel_message->getCreatedTime(), 'short'),
  ];

  $variables['id'] = $chat_channel_message->id();
  $variables['attributes'] = [
    'class' => [
      'chat-message',
      'js-chatChannelMessage',
    ],
    'data-channel-message-id' => $chat_channel_message->id(),
  ];

  if(isset($variables['elements']['#new_message']) && $variables['elements']['#new_message']) {
    $variables['attributes']['data-channel-message-new'] = TRUE;
  }
}

/**
 * Prepares variables for Chat channel message templates.
 *
 * Default template: chat_channel_message.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_chat_channel_message_divider(array &$variables) {
  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }

  $variables['content']['label'] = [
    '#type' => 'markup',
    '#markup' => $variables['elements']['#label'],
  ];

  if (isset($variables['elements']['#attributes'])) {
    $variables['attributes'] = $variables['elements']['#attributes'];
  }
  $variables['attributes']['class'][] = 'chat-channel-message-divider';
}