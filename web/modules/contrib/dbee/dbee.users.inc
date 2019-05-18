<?php

/**
 * @file
 * Encrypts or decrypts all user email addresses.
 */

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\encrypt\Entity\EncryptionProfile;

define('DBEE_ALL_USERS_CRYPT_LIMIT', 15);

/**
 * Encrypts or decrypts all user email addresses.
 *
 * This function encrypts or decrypts all user email addresses from the user
 * table. It is used when the dbee module is installed, enabled or disabled, or
 * when the encryption options are updated. it acts as simple callback or batch
 * operation if there are more than 15 users in database.
 *
 * @param string $action
 *   A string. Possible values are 'decrypt', 'encrypt', and 'change'. If set to
 *   'change', the $decrypt_params or the $encrypt_params must be provided.
 * @param string $dbee_context
 *   A string (optional). Context for processing, values are 'uninstall' and
 *   'change', default to FALSE.
 * @param bool $force_batch
 *   A boolean (optional). If TRUE the processes will be executed as batch
 *   operations, regardless of number of registerd users. Default to FALSE.
 */
function dbee_update_crypt_all($action, $dbee_context = FALSE, $force_batch = FALSE) {
  if (!in_array($action, ['decrypt', 'encrypt', 'change'])) {
    return FALSE;
  }

  // Count how much users will be processed.
  $db_users = dbee_stored_users();
  $n_users = count($db_users);

  if ($n_users <= 0) {
    // No users.
    return FALSE;
  }

  // Force to batch operation for decrypting emails on encrypt changes.
  if (!$force_batch && $n_users <= DBEE_ALL_USERS_CRYPT_LIMIT) {
    // Only 15 users to crypt. No batch operations.
    $context = [];
    dbee_update_crypt_users_all_batch_proceed($action, $dbee_context, $context);
    $success = $context['finished'] >= 1;
    $results = $context['results'];
    dbee_update_crypt_users_all_batch_finished($success, $results, []);
  }
  else {
    // Batch operation.
    $do_message = (($action == 'encrypt') ? t('encrypted') : (($action == 'decrypt') ? t('decrypted') : t('de-encrypted')));
    $batch = [
      'title' => t('@crypt all users', ['@crypt' => $do_message]),
      'operations' => [
        [
          'dbee_update_crypt_users_all_batch_proceed',
          [$action, $dbee_context],
        ],
      ],
      'finished' => 'dbee_update_crypt_users_all_batch_finished',
      'file' => drupal_get_path('module', 'dbee') . '/dbee.users.inc',
    ];
    batch_set($batch);

    // Processes the batch in cli mode.
    if (PHP_SAPI === 'cli') {
      $batch =& batch_get();
      $batch['progressive'] = FALSE;
      batch_process();
    }
  }
}

/**
 * Batch process for changing encryption of users (10 users per operation).
 *
 * @param string $action
 *   A string. Possible values are 'decrypt', 'encrypt', and 'change'. If set to
 *   'change', the $decrypt_params or the $encrypt_params must be provided.
 * @param string $dbee_context
 *   A string (optional). Context for processing, values are 'uninstall' and
 *   'change', default to FALSE.
 * @param array $context
 *   An array provided by reference, stores batch procesing informations.
 *
 * @see dbee_update_crypt_all()
 */
function dbee_update_crypt_users_all_batch_proceed($action, $dbee_context, array &$context) {
  if (!function_exists('dbee_unstore')) {
    // On batch operation, after the module is disabled, the dbee.module file is
    // not loaded anymore. We load it now for decrypting emails after the
    // dbee module is disabled.
    module_load_include('module', 'dbee');
  }
  if (empty($context['sandbox'])) {
    $context['sandbox']['progress'] = 0;
    $all_users = dbee_stored_users();
    $context['sandbox']['max'] = count($all_users);

    // Set parameters for displaying message on finished.
    $context['results']['users_total'] = $context['sandbox']['max'];
    $context['results']['dbee_action'] = $action;
    $context['results']['dbee_context'] = $dbee_context;
  }
  $limit = DBEE_ALL_USERS_CRYPT_LIMIT;
  $db_users = dbee_stored_users(NULL, $context['sandbox']['progress'], $limit);
  foreach ($db_users as $uid => $db_values) {
    // Change database encryption for this user.
    if (dbee_update_crypt_user($db_values, $action)) {
      $context['results']['users_updated'][] = $uid;
    }

    $context['sandbox']['progress']++;
    $context['message'] = t('processing user : %name (id : @id)', ['@id' => $uid, '%name' => $db_values['name']]);
  }
  if ($context['sandbox']['progress'] < $context['sandbox']['max']) {
    $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
  }
  else {
    $context['finished'] = 1;
  }
}

/**
 * Batch process finished callback for changing encryption on all users.
 *
 * @see dbee_update_crypt_all()
 */
function dbee_update_crypt_users_all_batch_finished($success, $results, $operations) {
  $n_updated = (isset($results['users_updated'])) ? count($results['users_updated']) : 0;
  $n_users = (isset($results['users_total'])) ? $results['users_total'] : 0;
  $action = (isset($results['dbee_action'])) ? $results['dbee_action'] : FALSE;
  $dbee_context = (isset($results['dbee_context'])) ? $results['dbee_context'] : FALSE;
  $real_success = ($success && $n_updated > 0);

  if ($action) {
    $do_message = (($action == 'encrypt') ? t('encrypted') : (($action == 'decrypt') ? t('decrypted') : t('de-crypted')));
    $message_arg = [
      '%crypted' => $do_message,
      '@updated_users' => $n_updated,
      '@total_users' => $n_users,
    ];
    if ($real_success) {
      $message_str = t('All users email addresses have been %crypted (concerning @updated_users of @total_users users)', $message_arg);
      $message_status = TRUE;
    }
    else {
      $message_str = t('Failing on the operation : users email addresses have been %crypted (concerning @updated_users of @total_users users)', $message_arg);
      $message_status = FALSE;
    }
    // Watchdog changes.
    $wd_status = ($message_status) ? 'info' : 'critical';
    \Drupal::logger('dbee')->{$wd_status}($message_str, $message_arg);
    $mess_status = ($message_status) ? MessengerInterface::TYPE_STATUS : MessengerInterface::TYPE_ERROR;
    \Drupal::messenger()->addMessage($message_str, $mess_status);

    switch ($dbee_context) {
      case 'uninstall':
        if ($real_success) {
          // Deleting the key will delete the corresponding encryption
          // profile to.
          if (!function_exists('dbee_unstore')) {
            // On batch operation, after the module is disabled, the dbee.module
            // file is not loaded anymore. We load it now for decrypting emails
            // after the dbee module is disabled.
            module_load_include('module', 'dbee');
          }
          $key_id = Drupal::service('key.repository')->getKey(DBEE_DEFAULT_KEY_NAME)->getOriginalId();
          if ($key_id) {
            // Only admin can delete, it may be used by other encryption stuff.
            \Drupal::messenger()->addStatus(t('You can delete the <a href="@url">Dbee key</a> if you don\'t use it anymore', ['@url' => 'admin/config/system/keys/manage/' . $key_id]));
          }

          // Reset Database storage lenght.
          // Parameters.
          $user_table = 'users_field_data';
          $mail_index_name = 'user_field__mail';
          $connection = \Drupal::service('database');
          // Increase mail and init storage length.
          $table_spec = [];
          foreach (['mail', 'init'] as $field) {
            $spec = [
              'type' => 'varchar',
              'length' => 254,
            ];
            $connection->schema()->changeField($user_table, $field, $field, $spec, []);
            $table_spec['fields'][$field] = $spec;
            $table_spec['fields'][$field]['not null'] = FALSE;
            $table_spec['indexes'] = [
              $mail_index_name => ['mail'],
            ];
          }
          if ($table_spec && !$connection->schema()->indexExists($user_table, $mail_index_name)) {
            $connection->schema()->addIndex($user_table, $mail_index_name, array('mail'), $table_spec);
          }
        }
        break;

      case 'change':
        // Delete prev parameters.
        if ($real_success) {
          $encrypt_profile = EncryptionProfile::load(DBEE_PREV_ENCRYPT_NAME);
          if ($encrypt_profile) {
            if ($encrypt_profile->delete()) {
              $key = Drupal::service('key.repository')->getKey(DBEE_PREV_KEY_NAME);
              if ($key) {
                $key->delete();
              }
            }
          }

        }
        break;
    }
  }
}

/**
 * Encrypts or decrypts one user email addresses.
 *
 * @param array $db_values
 *   An array of user database stored values. Keys are 'uid', 'mail', 'init'.
 * @param string $action
 *   A string. Possible values are 'decrypt', 'encrypt', and 'change'. If set to
 *   'change', the $decrypt_params or the $encrypt_params must be provided.
 *
 * @return bool
 *   A boolean, TRUE if the user has been updated.
 */
function dbee_update_crypt_user(array $db_values, $action) {

  $updated = FALSE;
  if ($action == 'decrypt') {
    // Decrypt every email addresses.
    $to_update = dbee_unstore($db_values);
  }
  elseif ($action == 'encrypt') {
    // Encrypt every email addresses.
    $to_update = dbee_store($db_values);
  }
  elseif ($action == 'change') {
    // First decrypt every email addresses.
    $to_update = dbee_unstore($db_values, TRUE);
  }

  // Handle sc mails.
  $changes = [];
  foreach (['mail', 'init'] as $dbee_field) {

    // {users_field_data} table.
    if (isset($to_update[$dbee_field]) && $to_update[$dbee_field] != $db_values[$dbee_field]) {
      $changes[$dbee_field] = $to_update[$dbee_field];
    }
  }

  $uid = $db_values['uid'];
  // Apply changes on lowercase case storing.
  if (!empty($changes)) {
    // Processed to the update of the user table.
    $updated = TRUE;
    $query = \Drupal::database()->update('users_field_data')
      ->fields($changes)
      ->condition('uid', $uid)
      ->execute();
  }
  return $updated;
}
