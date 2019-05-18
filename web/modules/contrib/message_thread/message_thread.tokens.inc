<?php

/**
 * @file
 * Builds placeholder replacement tokens for message-related data.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\user\Entity\User;

/**
 * Implements hook_token_info().
 */
function message_thread_token_info() {
  $type = [
    'name' => t('message threads'),
    'description' => t('Tokens related to individual content items, or "message threads".'),
    'needs-data' => 'message_thread',
  ];

  // Core tokens for message threads.
  $message_thread['thread_id'] = [
    'name' => t("Thread ID"),
    'description' => t('The unique ID of the message thread.'),
  ];
  $message_thread['template'] = [
    'name' => t("Message thread template"),
  ];
  $message_thread['template-name'] = [
    'name' => t("message thread template name"),
    'description' => t("The human-readable name of the message thread template."),
  ];
  $message_thread['uuid'] = [
    'name' => t("Message thread UID"),
    'description' => t("The message thread UUID."),
  ];

  $message_thread['created'] = [
    'name' => t("Date created"),
    'type' => 'date',
  ];
  $message_thread['author'] = [
    'name' => t("Author"),
    'type' => 'user',
  ];
  return [
    'types' => ['message_thread' => $type],
    'tokens' => ['message_thread' => $message_thread],
  ];
}

/**
 * Implements hook_tokens().
 */
function message_thread_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $token_service = \Drupal::token();

  $replacements = [];

  if ($type == 'message_thread' && !empty($data['message_thread'])) {
    /** @var \Drupal\message\Entity\Message $message_thread */
    $message_thread = $data['message_thread'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        // Simple key values on the message.
        case 'thread_id':
          $replacements[$original] = $message_thread->id();
          break;

        case 'template':
          $replacements[$original] = $message_thread->getTemplate()->id();
          break;

        case 'template-name':
          $replacements[$original] = $message_thread->getTemplate()->label();
          break;

        case 'uuid':
          $replacements[$original] = $message_thread->getUuid();
          break;

        // Default values for the chained tokens handled below.
        case 'author':
          $account = $message_thread->getOwner() ? $message_thread->getOwner() : User::load(0);
          $replacements[$original] = $account->label();
          break;

        case 'created':
          $replacements[$original] = \Drupal::service('date.formatter')->format($message_thread->getCreatedTime(), 'medium', '', NULL);
          break;
      }
    }

    if ($author_tokens = $token_service->findWithPrefix($tokens, 'author')) {
      $replacements += $token_service->generate('user', $author_tokens, ['user' => $message_thread->getOwner()], $options, $bubbleable_metadata);
    }

    if ($created_tokens = $token_service->findWithPrefix($tokens, 'created')) {
      $replacements += $token_service->generate('date', $created_tokens, ['date' => $message_thread->getCreatedTime()], $options, $bubbleable_metadata);
    }
  }

  return $replacements;
}
