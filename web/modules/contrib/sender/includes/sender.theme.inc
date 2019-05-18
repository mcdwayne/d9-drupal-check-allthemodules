<?php

/**
 * @file
 * Theme related hook implementations.
 */

/**
 * Implements hook_theme().
 */
function sender_theme($existing, $type, $theme, $path) {

  /*
   * Available variables:
   * '#subject' = The message's subject.
   * '#body_text' = The message's body.
   * '#body_format' = The format of the message's body.
   * '#message_id' = The message's ID.
   * '#method' = The method used to send the message (optional).
   */
  $info['sender_message'] = [
    'variables' => [
      'subject' => '',
      'body_text' => '',
      'body_format' => 'basic_html',
      'message_id' => NULL,
      'method' => NULL,
    ],
  ];

  return $info;
}

/**
 * Implements template_preprocess_HOOK().
 */
function template_preprocess_sender_message(array &$variables) {
  // Replaces the text by a render array that will render a plain text.
  $variables['subject'] = [
    '#plain_text' => $variables['subject'],
  ];

  // Replaces the text by a render array that will render the processed text.
  $variables['body'] = [
    '#type' => 'processed_text',
    '#text' => $variables['body_text'],
    '#format' => $variables['body_format'],
  ];
}

/**
 * Implements hook_theme_suggestions_HOOK().
 */
function sender_theme_suggestions_sender_message(array $variables) {
  $suggestions = [
    'sender_message__' . $variables['message_id'],
  ];

  // Adds theme hook suggestions if method is set.
  if (isset($variables['method'])) {
    // Adds low priority theme hook suggestions.
    array_unshift($suggestions, 'sender_message__' . $variables['method']);

    // The theme hook suggestion sender_message__ID_METHOD has the highest
    // priority.
    $suggestions[] = 'sender_message__' . $variables['message_id'] . '_' . $variables['method'];
  }

  return $suggestions;
}
