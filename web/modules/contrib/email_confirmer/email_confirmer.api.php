<?php

/**
 * @file
 * Document api for email confirmer module.
 */

use Drupal\email_confirmer\EmailConfirmationInterface;

/**
 * Acts on email confirmation responses.
 *
 * This hook allows a module to get notified when a confirmation response
 * is received.
 *
 * @param string $op
 *   Either "confirm" or "cancel".
 * @param \Drupal\email_confirmer\EmailConfirmationInterface $confirmation
 *   The confirmation process.
 */
function hook_email_confirmer($op, EmailConfirmationInterface $confirmation) {
  // Log the event.
  \Drupal::logger('email_confirmer')->info('Email confirmation @cid ' . ($op == 'confirm' ? 'confirmed' : 'cancelled'), ['@cid' => $confirmation->id() ?: '-not saved-']);
}
