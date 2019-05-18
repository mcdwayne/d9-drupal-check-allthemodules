<?php

/**
 * @file
 * Invite tokens.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Link;

/**
 * Implements hook_token_info().
 */
function invite_by_email_token_info() {
  $info = [];
  $info['types']['invite'] = [
    'name' => t('Invite'),
    'description' => ('Invite by email tokens.'),
  ];

  $info['tokens']['invite']['inviter:name'] = [
    'name' => t('Inviter name'),
    'description' => t('Name of the invitation creator.'),
    'dynamic' => TRUE,
  ];

  $info['tokens']['invite']['invite-accept-link'] = [
    'name' => t('Accept link'),
    'description' => t('Link to accept the invitation.'),
    'dynamic' => TRUE,
  ];

  return $info;
}

/**
 * Implements hook_tokens().
 */
function invite_by_email_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];
  if ($type == 'invite') {

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'inviter:name':
          $replacements[$original] = $data['invite']->getOwner()->getAccountName();
          break;

        case 'invite-accept-link':
          $replacements[$original] = Link::createFromRoute('accept invite', 'invite.invite_accept_accept', ['invite' => $data['invite']->getRegCode()], ['absolute' => TRUE])->getUrl()->toString();
          break;

        default:
      }
    }
  }

  return $replacements;
}
