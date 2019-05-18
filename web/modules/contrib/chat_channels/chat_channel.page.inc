<?php

/**
 * @file
 * Contains chat_channel.page.inc.
 *
 * Page callback for Chat channel entities.
 */

/**
 * Prepares variables for Chat channel templates.
 *
 * Default template: chat_channel.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_chat_channel(array &$variables) {
  /** @var \Drupal\chat_channels\Entity\ChatChannelInterface $chat_channel */
  $chat_channel = $variables['elements']['#chat_channel'];

  // Set the channel id for the twig template.
  $variables['channel_id'] = $chat_channel->id();

}

/**
 * Prepares variables for Chat channel templates.
 *
 * Default template: chat_channel.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_chat_channel_new_message_indicator(array &$variables) {
  $variables['content']['count'] = [
    '#type' => 'markup',
    '#markup' => !empty($variables['elements']['#count']) ? $variables['elements']['#count'] : '0',
  ];

  if (isset($variables['elements']['#attributes'])) {
    $variables['attributes'] = $variables['elements']['#attributes'];
  }

  $variables['attributes']['class'][] = 'indicator--new-message js-newMessageIndicator';
  if (empty($variables['elements']['#count'])) {
    $variables['attributes']['class'][] = 'empty';
  }
}