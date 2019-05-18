<?php

/**
 * @file
 * Builds placeholder replacement tokens for message-related data.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\user\Entity\User;
use Drupal\Core\Link;

/**
 * Implements hook_token_info().
 */
function friends_token_info() {
  $type = [
    'name' => t('friends'),
    'description' => t('Tokens related to individual content items, or "friends".'),
    'needs-data' => 'friends',
  ];

  // Core tokens for messages.
  $tokens['id'] = [
    'name' => t("friends ID"),
    'description' => t('The unique ID of the message.'),
  ];
  $tokens['created'] = [
    'name' => t("Date created"),
    'type' => 'date',
  ];
  $tokens['author'] = [
    'name' => t("Author"),
    'type' => 'user',
  ];
  $tokens['recipient'] = [
    'name' => t("Recipient"),
    'type' => 'user',
  ];
  $tokens['type'] = [
    'name' => t("Type of Request"),
  ];
  $tokens['response-message'] = [
    'name' => t("Response message depending on status"),
  ];
  $tokens['request-message'] = [
    'name' => t("Request message"),
  ];
  $tokens['accept-request-link'] = [
    'name' => t("Accept Request Link"),
  ];
  $tokens['decline-request-link'] = [
    'name' => t("Decline Request Link"),
  ];

  return [
    'types' => ['friends' => $type],
    'tokens' => ['friends' => $tokens],
  ];
}

/**
 * Implements hook_tokens().
 */
function friends_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $token_service = \Drupal::token();

  $replacements = [];

  if ($type == 'friends' && !empty($data['friends'])) {
    /** @var \Drupal\friends\Entity\Friends $friends */
    $friends = $data['friends'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        // Simple key values on the message.
        case 'id':
          $replacements[$original] = $friends->id();
          break;

        case 'author':
          // Default values for the chained tokens handled below.
          $account = $friends->getOwner() ? $friends->getOwner() : User::load(0);
          $replacements[$original] = $account->label();
          break;

        case 'recipient':
          // Default values for the chained tokens handled below.
          $account = $friends->getRecipient() ? $friends->getRecipient() : User::load(0);
          $replacements[$original] = $account->label();
          break;

        case 'created':
          $replacements[$original] = \Drupal::service('date.formatter')->format($friends->getCreatedTime(), 'medium', '', NULL);
          break;

        case 'type':
          $replacements[$original] = $friends->getType();
          break;

        case 'response-message':
          $replacements[$original] = \Drupal::service('friends.default')->getMessage($friends->get('friends_status')->value, $friends);
          break;

        case 'request-message':
          $replacements[$original] = \Drupal::service('friends.default')->getMessage('request', $friends);
          break;

        case 'accept-request-link':
          $replacements[$original] = Link::fromTextAndUrl(
            t('Accept'),
            $friends->getStatusUrl('accepted')
          )->toString();
          break;

        case 'decline-request-link':
          $replacements[$original] = Link::fromTextAndUrl(
            t('Decline'),
            $friends->getStatusUrl('declined')
          )->toString();
          break;
      }
    }

    if ($author_tokens = $token_service->findWithPrefix($tokens, 'author')) {
      $replacements += $token_service->generate('user', $author_tokens, ['user' => $friends->getOwner()], $options, $bubbleable_metadata);
    }

    if ($recipient_tokens = $token_service->findWithPrefix($tokens, 'recipient')) {
      $replacements += $token_service->generate('user', $recipient_tokens, ['user' => $friends->getRecipient()], $options, $bubbleable_metadata);
    }

    if ($created_tokens = $token_service->findWithPrefix($tokens, 'created')) {
      $replacements += $token_service->generate('date', $created_tokens, ['date' => $friends->getCreatedTime()], $options, $bubbleable_metadata);
    }
  }

  return $replacements;
}
