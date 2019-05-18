<?php

/**
 * @file
 * Token related hook implementations.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Url;

/**
 * Implements hook_token_info().
 */
function email_confirmer_token_info() {
  $types['email-confirmer'] = [
    'name' => t('Email confirmer'),
    'description' => t('Tokens related to the email confirmation process'),
    'needs-data' => 'email_confirmer_confirmation',
  ];

  $tokens['confirmation-url'] = [
    'name' => t('Confirmation URL'),
    'description' => t('The URL of the page where users can confirm their email address.'),
  ];

  $tokens['confirmation-mail'] = [
    'name' => t('Confirmation email'),
    'description' => t('The email address to confirm.'),
  ];

  return [
    'types' => $types,
    'tokens' => [
      'email-confirmer' => $tokens,
    ],
  ];
}

/**
 * Implements hook_tokens().
 */
function email_confirmer_tokens($type, $tokens, $data, $options, BubbleableMetadata $bubbleable_metadata) {
  if ($type != 'email-confirmer' || !isset($data['email_confirmer_confirmation'])) {
    return [];
  }

  $replacements = [];

  /** @var \Drupal\email_confirmer\EmailConfirmationInterface $confirmation */
  $confirmation = $data['email_confirmer_confirmation'];

  foreach ($tokens as $name => $original) {
    switch ($name) {
      case 'confirmation-url':
        $replacements[$original] = Url::fromRoute(
          'entity.email_confirmer_confirmation.response_form',
          ['email_confirmer_confirmation' => $confirmation->uuid(), 'hash' => $confirmation->getHash()])->setAbsolute()->toString();
        $bubbleable_metadata->setCacheMaxAge(0);
        break;

      case 'confirmation-mail':
        $replacements[$original] = $confirmation->getEmail();
        break;
    }
  }

  return $replacements;
}
