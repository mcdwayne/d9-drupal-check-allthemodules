<?php

/**
 * @file
 * Token related hook implementations.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Language\LanguageInterface;

/**
 * Implements hook_token_info().
 */
function mailing_list_token_info() {
  $types['mailing-list-subscription'] = [
    'name' => t('Mailing list subscription'),
    'description' => t('Tokens for mailing list subscriptions'),
    'needs-data' => 'mailing_list_subscription',
  ];

  // Basic fields.
  $tokens['sid'] = [
    'name' => t('Subscription ID'),
    'description' => t('The unique ID of the subscription.'),
  ];

  $tokens['list'] = [
    'name' => t('Mailing list ID'),
  ];

  $tokens['list-name'] = [
    'name' => t('Mailing list name'),
    'description' => t('The human-readable name of the subscriptions mailing list.'),
  ];

  $tokens['title'] = [
    'name' => t('Title'),
  ];

  $tokens['email'] = [
    'name' => t('Email'),
    'description' => t('The subscription email.'),
  ];

  $tokens['obfuscated-email'] = [
    'name' => t('Obfuscated email'),
    'description' => t("The subscription email obfuscated by replacing some characters with '*'."),
  ];

  $tokens['langcode'] = [
    'name' => t('Language code'),
    'description' => t("The subscription's language code."),
  ];

  $tokens['url'] = [
    'name' => t('URL'),
    'description' => t('The view URL of the subscription.'),
  ];

  $tokens['edit-url'] = [
    'name' => t("Edit URL"),
    'description' => t("The URL of the subscription's edit page."),
  ];

  $tokens['cancel-url'] = [
    'name' => t("Cancel URL"),
    'description' => t("The URL for subscription cancellation."),
  ];

  $tokens['unrestricted-view-url'] = [
    'name' => t('Unrestricted view URL'),
    'description' => t('A URL to view a subscription with permissions granted by an access hash.'),
  ];

  $tokens['unrestricted-edit-url'] = [
    'name' => t('Unrestricted edit URL'),
    'description' => t('A URL to edit a subscription with permissions granted by an access hash.'),
  ];

  $tokens['unrestricted-cancel-url'] = [
    'name' => t('Unrestricted cancel URL'),
    'description' => t('A URL to cancel a subscription with permissions granted by an access hash.'),
  ];

  $tokens['unrestricted-manage-url'] = [
    'name' => t('Unrestricted manage URL'),
    'description' => t('A URL to manage user subscriptions with permissions granted by an access hash.'),
  ];

  // Chained tokens for subscriptions.
  $tokens['created'] = [
    'name' => t("Date created"),
    'type' => 'date',
  ];

  $tokens['changed'] = [
    'name' => t("Date changed"),
    'description' => t("The date the subscription was most recently updated."),
    'type' => 'date',
  ];

  $tokens['owner'] = [
    'name' => t("Owner"),
    'type' => 'user',
  ];

  return [
    'types' => $types,
    'tokens' => [
      'mailing-list-subscription' => $tokens,
    ],
  ];
}

/**
 * Implements hook_tokens().
 */
function mailing_list_tokens($type, $tokens, $data, $options, BubbleableMetadata $bubbleable_metadata) {
  if ($type != 'mailing-list-subscription' || !isset($data['mailing_list_subscription'])) {
    return [];
  }

  $url_options = ['absolute' => TRUE];
  if (isset($options['langcode'])) {
    $url_options['language'] = \Drupal::languageManager()->getLanguage($options['langcode']);
    $langcode = $options['langcode'];
  }
  else {
    $langcode = LanguageInterface::LANGCODE_DEFAULT;
  }

  $replacements = [];

  /** @var \Drupal\mailing_list\SubscriptionInterface $subscription */
  $subscription = $data['mailing_list_subscription'];

  foreach ($tokens as $name => $original) {
    switch ($name) {
      case 'sid':
        $replacements[$original] = $subscription->id();
        break;

      case 'list':
        $replacements[$original] = $subscription->getListId();
        break;

      case 'list-name':
        $replacements[$original] = $subscription->getList()->label();
        break;

      case 'title':
        $replacements[$original] = $subscription->getTitle();
        break;

      case 'email':
        $replacements[$original] = $subscription->getEmail();
        break;

      case 'obfuscated-email':
        $replacements[$original] = $subscription->getEmail(TRUE);
        break;

      case 'langcode':
        $replacements[$original] = $subscription->language()->getId();
        break;

      case 'url':
        $replacements[$original] = $subscription->url('canonical', $url_options);
        break;

      case 'edit-url':
        $replacements[$original] = $subscription->url('edit-form', $url_options);
        break;

      case 'cancel-url':
        $replacements[$original] = $subscription->url('delete-form', $url_options);
        break;

      case 'unrestricted-view-url':
        $replacements[$original] = Url::fromRoute(
          'mailing_list.access_subscription', [
            'sid' => $subscription->id(),
            'hash' => $subscription->getAccessHash(),
            'rel' => 'view',
          ], $url_options)->toString();
        break;

      case 'unrestricted-edit-url':
        $replacements[$original] = Url::fromRoute(
          'mailing_list.access_subscription', [
            'sid' => $subscription->id(),
            'hash' => $subscription->getAccessHash(),
            'rel' => 'edit-form',
          ], $url_options)->toString();
        break;

      case 'unrestricted-cancel-url':
        $replacements[$original] = Url::fromRoute(
          'mailing_list.access_subscription', [
            'sid' => $subscription->id(),
            'hash' => $subscription->getAccessHash(),
            'rel' => 'delete-form',
          ], $url_options)->toString();
        break;

      case 'unrestricted-manage-url':
        $replacements[$original] = Url::fromRoute(
          'mailing_list.access_subscription', [
            'sid' => $subscription->id(),
            'hash' => $subscription->getAccessHash(),
            'rel' => 'manage',
          ], $url_options)->toString();
        break;

      // Default values for the chained tokens handled below.
      case 'owner':
        $account = $subscription->getOwner() ? $subscription->getOwner() : User::getAnonymousUser();
        $bubbleable_metadata->addCacheableDependency($account);
        $replacements[$original] = $account->label();
        break;

      case 'created':
        $date_format = DateFormat::load('medium');
        $bubbleable_metadata->addCacheableDependency($date_format);
        $replacements[$original] = format_date($subscription->getCreatedTime(), 'medium', '', NULL, $langcode);
        break;

      case 'changed':
        $date_format = DateFormat::load('medium');
        $bubbleable_metadata->addCacheableDependency($date_format);
        $replacements[$original] = format_date($subscription->getChangedTime(), 'medium', '', NULL, $langcode);
        break;
    }
  }

  $token_service = \Drupal::token();
  if ($owner_tokens = $token_service->findWithPrefix($tokens, 'owner')) {
    $replacements += $token_service->generate('user', $owner_tokens, ['user' => $subscription->getOwner()], $options, $bubbleable_metadata);
  }

  if ($created_tokens = $token_service->findWithPrefix($tokens, 'created')) {
    $replacements += $token_service->generate('date', $created_tokens, ['date' => $subscription->getCreatedTime()], $options, $bubbleable_metadata);
  }

  if ($changed_tokens = $token_service->findWithPrefix($tokens, 'changed')) {
    $replacements += $token_service->generate('date', $changed_tokens, ['date' => $subscription->getChangedTime()], $options, $bubbleable_metadata);
  }

  return $replacements;
}
