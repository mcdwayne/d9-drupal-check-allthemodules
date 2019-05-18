<?php

/**
 * @file
 * Documents hooks provided by the ACSF Duplication module.
 */

/**
 * Alters the counts reported by `drush acsf-duplication-scrub-progress`.
 *
 * @param array $data
 *   An associative array of counts representing the total number of items
 *   remaining to scrub, keyed by [type]_count.
 *
 * @see drush_acsf_duplication_scrub_progress()
 */
function hook_acsf_duplication_scrub_remaining_counts_alter(array &$data) {
  $example_event = \Drupal\acsf\Event\AcsfEvent::create('site_duplication_scrub');
  $data['node_count'] = (new \Drupal\acsf\Event\AcsfDuplicationScrubCommentHandler($example_event))
    ->countRemaining();
}

/**
 * Alters the scrub event context of `drush acsf-duplication-scrub-batch`.
 *
 * Use this alter hook to add optional data to the scrub event. The data added
 * here is available via the $this->event->context array in event handlers.
 *
 * @param array $context
 *   An associative array of context data needed in the event handlers.
 * @param array $options
 *   An associative array of drush command options.
 *
 * @see drush_acsf_duplication_scrub_batch()
 */
function hook_acsf_duplication_scrub_context_alter(array &$context, array $options) {
  $context['scrub_options']['retain_users'] = $options['exact-copy'];
  $context['scrub_options']['retain_content'] = $options['exact-copy'];
}

/**
 * Alters the list of admin roles of users to preserve.
 *
 * @param array $admin_roles
 *   A numeric array of admin roles of users to preserve.
 *
 * @see \Acquia\Acsf\AcsfDuplicationScrubUserHandler::getOpenIdAdmins()
 */
function hook_acsf_duplication_scrub_admin_roles_alter(array &$admin_roles) {
  if ($role = \Drupal::config('mymodule')->get('admin_role')) {
    $admin_roles[] = $role;
  }
}

/**
 * Alters the list of user IDs to preserve.
 *
 * @param array $preserved_uids
 *   A numeric array of user IDs to preserve.
 *
 * @see \Acquia\Acsf\AcsfDuplicationScrubUserHandler::getPreservedUsers()
 */
function hook_acsf_duplication_scrub_preserved_users_alter(array &$preserved_uids) {
  if ($uids = \Drupal::config('mymodule')->get('preserved_uids', [])) {
    $preserved_uids = array_merge($preserved_uids, $uids);
  }
}
