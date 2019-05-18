<?php

/**
 * @file
 * Hooks provided by the commandbar module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the matches returned from a commandbar autocomplete search.
 *
 * This hook is invoked in the getMatches() method of the CommandbarAutocomplete
 * class before the menu is searched for matches. The $matches variable is an
 * array passed be reference that begins empty and should be formatted in the
 * way that autocomplete $matches are typically returned, with the key being the
 * content to use in the input, and the value being the text to display in the
 * result. If $continue is set to FALSE, it will stop any subsequent alter hooks
 * as well as the menu search.This is useful for taking over the commandbar.
 *
 * @param $matches
 *   An array of matches for the commandbar autocomplete, starting empty.
 * @param $continue
 *   If FALSE, subsequent alter hooks an the menu search will be skipped.
 * @param $string
 *   The string that has be input into the commandbar.
 **/
function hook_commandbar_build_alter(&$matches, &$continue, $string) {

  // Respect the request of other modules to not continue.
  if (!$continue) {
    return;
  }

  // This example shows using the @ character to signify that there should be a
  // search for users.
  if (substr($string, 0, 1) == '@') {
    $string = str_replace('@', '', $string);
    $or = db_or();
    if (strlen($string) > $strlen) {
      $or->condition('name', '%' . db_like($string) . '%', 'LIKE');
      $result = Drupal::database()->select('users')->fields('users', array('name', 'uid', 'mail'))->condition($or)->range(0, 25)->execute();
      foreach ($result as $account) {
        $path = 'user/' . $account->uid;
        $matches['user/' . $account->uid] = theme('commandbar_user_item', array('path' => $path, 'uid' => $account->uid, 'name' => $account->name, 'mail' => $account->mail));
      }
    }

    // Since this hook took over for all patterns starting with @, we're setting
    // $continue to false to make sure no other searches are done.
    $continue = FALSE;
  }
}

/**
 * Alter the matches after they have been compiled.
 *
 * This hook can be used after all of the matches for the commandbar
 * autocomplete have been generated. This can be useful for removing or
 * modifying matches generated during the build process.
 *
 * @param $matches
 *   An array that matches the expectations of an autocomplete input, with the
 *   key being the text that goes into the input, and the value being the
 *   content that represents that value in the autocomplete drop-down.
 */
function hook_commandbar_matches_alter(&$matches) {
  // Here we're removing any matches that have 'block' in the path.
  foreach ($matches as $path => $output) {
    if (strstr($path, 'block')) {
      unset($matches[$path]);
    }
  }
}

/**
 * Alters the behavior of the commandbar submit method.
 *
 * This hook may alter or add to the behavior of the submit method used for the
 * commandbar. By default, the user will be forwarded to the path displayed in
 * the commandbar. This hook may add additonal behaviors and may prevent the
 * default behavior from happening. If $continue is set to false, no further
 * actions will take place.
 *
 * @param $string
 *   The content of the commandbar input when submitted.
 * @param $continue
 *   If set to FALSE, this will prevent the default behavior as well as other
 *   alter hooks that respect the $continue status.
 */
function hook_commandbar_submit_alter($string, &$continue) {
  // Instead of jumping to a particular path, we're going to clear the caches
  // and go back to the current page.
  if ($string == 'cc') {
    drupal_flush_all_caches();
    drupal_set_message(t('Caches cleared.'));
    $continue = FALSE;
  }
}

/**
 * @} End of "addtogroup hooks".
 */