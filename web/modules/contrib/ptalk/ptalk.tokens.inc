<?php

/**
 * @file
 * Builds placeholder replacement tokens for ptalk module related data.
 */

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function ptalk_token_info() {
  $types['message'] = [
    'name' => t("Messages"),
    'description' => t("Tokens related to private conversation messages."),
    'needs-data' => 'message',
  ];
  $types['thread'] = [
    'name' => t("Threads"),
    'description' => t("Tokens related to private conversation threads."),
    'needs-data' => 'thread',
  ];

  // Private conversation message related variables.
  $message['mid'] = [
    'name' => t("Message ID"),
    'description' => t("The unique ID of the message."),
  ];
  $message['tid'] = [
    'name' => t("Conversation ID"),
    'description' => t("The unique ID of the related private conversation."),
  ];
  $message['author'] = [
    'name' => t("Author"),
    'description' => t("The author of the message."),
  ];
  $message['subject'] = [
    'name' => t("Subject"),
    'description' => t("The subject of the message."),
  ];
  $message['body'] = [
    'name' => t("Content"),
    'description' => t("The formatted content of the message itself."),
  ];
  $message['created'] = [
    'name' => t("Date created"),
    'description' => t("The date the message was posted."),
    'type' => 'date',
  ];
  $message['changed'] = [
    'name' => t("Date changed"),
    'description' => t("The date the message was updated."),
    'type' => 'date',
  ];
  $message['url'] = [
    'name' => t("URL"),
    'description' => t("The URL of the related private conversation to which message is belongs."),
  ];

  // Private conversation thread related variables.
  $thread['tid'] = [
    'name' => t("Thread ID"),
    'description' => t("The unique ID of the private conversation."),
  ];
  $thread['author'] = [
    'name' => t("Author"),
    'description' => t("The author of the private conversation."),
  ];
  $thread['subject'] = [
    'name' => t("Subject"),
    'description' => t("The subject of the private conversation."),
  ];
  $thread['participants'] = [
    'name' => t("Participants"),
    'description' => t("The unique participants IDS of the private conversation."),
  ];
  $thread['last-message-author'] = [
    'name' => t("Last message author"),
    'description' => t("The author which posted the last message of the private conversation."),
  ];
  $thread['created'] = [
    'name' => t("Date created"),
    'description' => t("The date the private conversation was created."),
    'type' => 'date',
  ];
  $thread['changed'] = [
    'name' => t("Date changed"),
    'description' => t("The date the private conversation was updated."),
    'type' => 'date',
  ];
  $thread['url'] = [
    'name' => t("URL"),
    'description' => t("The URL of the private conversation."),
  ];

  return [
    'types' => $types,
    'tokens' => [
      'message' => $message,
      'thread' => $thread,
    ],
  ];
}

/**
 * Implements hook_tokens().
 */
function ptalk_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $token_service = \Drupal::token();

  $replacements = [];
  if ($type == 'message' && !empty($data['message'])) {
    $message = $data['message'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'mid':
          $replacements[$original] = $message->id();
          break;

        case 'tid':
          $replacements[$original] = $message->getThreadId();
          break;

        case 'author':
          $name = $message->getOwner()->getUserName();
          $replacements[$original] = $name;
          break;

        case 'subject':
          $subject = $message->label();
          $replacements[$original] = $subject;
          break;

        case 'body':
          $replacements[$original] = $message->body->processed;
          break;

        case 'created':
          $date_format = DateFormat::load('medium');
          $bubbleable_metadata->addCacheableDependency($date_format);
          $replacements[$original] = format_date($message->getCreatedTime(), 'medium', '', NULL, NULL);
          break;

        case 'changed':
          $date_format = DateFormat::load('medium');
          $bubbleable_metadata->addCacheableDependency($date_format);
          $replacements[$original] = format_date($message->getChangedTime(), 'medium', '', NULL, NULL);
          break;

        case 'url':
          $replacements[$original] = $message->getThread()->urlInfo();
          break;
      }
    }
  }

  elseif ($type == 'thread' && !empty($data['thread'])) {
    $thread = $data['thread'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'tid':
          $replacements[$original] = $thread->id();
          break;

        case 'author':
          $name = $thread->getOwner()->getUserName();
          $replacements[$original] = $name;
          break;

        case 'subject':
          $subject = $thread->label();
          $replacements[$original] = $subject;
          break;

        case 'participants':
          $replacements[$original] = $thread->participants->value;
          break;

        case 'last-message-author':
          $replacements[$original] = $thread->getLastMessageAuthor()->getUserName();
          break;

        case 'created':
          $date_format = DateFormat::load('medium');
          $bubbleable_metadata->addCacheableDependency($date_format);
          $replacements[$original] = format_date($thread->getCreatedTime(), 'medium', '', NULL, NULL);
          break;

        case 'changed':
          $date_format = DateFormat::load('medium');
          $bubbleable_metadata->addCacheableDependency($date_format);
          $replacements[$original] = format_date($thrad->getChangedTime(), 'medium', '', NULL, NULL);
          break;

        case 'url':
          $replacements[$original] = $thread->urlInfo();
          break;
      }
    }
  }

  return $replacements;
}
