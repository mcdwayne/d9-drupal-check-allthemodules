<?php

/**
 * @file
 * Hooks of the Consent module.
 */

/**
 * Act upon the consent information before being saved.
 *
 * @param \Drupal\consent\ConsentInterface $consent
 *   The consent information which is about to be saved.
 */
function hook_before_consent_save(\Drupal\consent\ConsentInterface $consent) {
  $consent->set('my_info_key', 'my_certain_value');
}
