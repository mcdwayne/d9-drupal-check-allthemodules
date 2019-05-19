<?php

/**
 * @file
 * Social_auth_itsme API documentation file.
 */

/**
 * Modify the locale being sent to itsme.
 *
 * The value returned from this alter must be one of "en", "nl", "fr" or "de".
 *
 * @param string $language_id
 *   The ID of the current language.
 *
 * @see \Drupal\social_auth_itsme\ItsmeAuthManager
 */
function hook_social_auth_itsme_locale_alter(&$language_id) {
  $language_id = 'nl';
}
