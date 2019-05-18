<?php

/**
 * Containt class Drupal\delete_users_csv\DeleteUsersBatch
 */

namespace Drupal\delete_users_csv;

class DeleteUsersBatch {

  /**
   * @param $users_emails, array of users emails.
   * @param $context
   */
  public static function deleteUsers($users_emails, &$context){
    $message = 'Deleting users...';
    $results = array();
    if (!empty($users_emails)) {
      foreach ($users_emails as $key => $value) {
        $user = user_load_by_mail($value);
        if (!empty($user)) {
          $results[] = $user->delete();
        }
      }
    }
    $context['message'] = $message;
    $context['results'] = $results;
  }

  /**
   * @param $success
   * @param $results
   * @param $operations
   */
  function deleteUsersCallback($success, $results, $operations) {
    // The 'success' parameter means no fatal PHP errors were detected. All
    // other error management should be handled using 'results'.
    if ($success) {
      $message = \Drupal::translation()->formatPlural(
        count($results),
        'One user deleted.', '@count users deleted.'
      );
    }
    else {
      $message = t('Finished with an error.');
    }
    drupal_set_message($message);
  }

}