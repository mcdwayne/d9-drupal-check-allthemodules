<?php

/**
 * @file
 * Provides the drop-the-ripper drush command.
 */

use Drush\Log\LogLevel;

/**
 * Implements hook_drush_command().
 */
function drop_the_ripper_drush_command() {
  $items = array();

  $items['drop-the-ripper'] = array(
    'description' => 'Crack Drupal password hashes.',
    'callback' => 'drush_drop_the_ripper',
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_FULL,
    'arguments' => array(
      'user-rids' => '(Optional) Only check passwords for users with these role IDs (comma separate multiple IDs).',
    ),
    'options' => array(
      'wordlist' => array(
        'description' => 'Path to a custom wordlist (default is openwall\'s password list).',
        'example-value' => '/path/to/wordlist',
        'value' => 'required',
      ),
      'top' => array(
        'description' => 'Number of passwords to read from the wordlist (default is 25).',
        'example-value' => '25',
        'value' => 'required',
      ),
      'all' => array(
        'description' => 'Use all entries from the wordlist (default if a custom wordlist is supplied).',
      ),
      'hide' => array(
        'description' => 'Do not show plaintext passwords in output.',
      ),
      'uid' => array(
        'description' => 'Comma separated list of user ids.',
      ),
      'restricted' => array(
        'description' => 'Check all users with roles that have restricted (admin) permissions.',
      ),
      'no-guessing' => array(
        'description' => 'Disables built-in password guessing (e.g. username as password).',
      ),
    ),
    'examples' => array(
      'drush dtr' => 'Try to crack passwords of all users.',
      'drush dtr --top=100' => 'Try to crack passwords of all users, using the first 100 passwords from the wordlist.',
      'drush dtr 3' => 'Try to crack passwords of all users with role 3 in D7 ("drush rls" lists role IDs).',
      'drush dtr editor' => 'Try to crack passwords of all users with editor role in D8 ("drush rls" lists role IDs).',
      'drush dtr --uid=1' => 'Try to crack password of user number 1.',
      'drush dtr --restricted' => 'Try to crack passwords of all users with roles that have restricted permissions.',
      'drush dtr --wordlist=/tmp/rockyou.txt' => 'Use a custom wordlist for password cracking.',
      'drush dtr --all --no-guessing' => 'Try every password in the wordlist, but do not try to guess user passwords.',
    ),
    'aliases' => array('dtr'),
  );

  return $items;
}

/**
 * Drush command callback.
 *
 * @param string $user_rids
 *   Comma-separated list of role rids.
 */
function drush_drop_the_ripper($user_rids = 'all') {

  $drupal_version = drush_drupal_major_version();
  switch ($drupal_version) {
    case 8:
      _drush_drop_the_ripper_d8($user_rids);
      break;
    case 7:
      _drush_drop_the_ripper_d7($user_rids);
      break;
    default:
      // Password handling before D7 was quite different; for example in D6
      // user_load() was called with the username and password and the password
      // was md5 hashed before being sent to the db in a SQL query to see if a
      // row in the users table matches. There was also no DBTNG in core.
      // It would be quite a lot of work to implement dtr properly for what is
      // now an unsupported version of Drupal core.
      $msg = "Drop the Ripper doesn't support this version of Drupal. Sorry.";
      return drush_set_error('DRUSH_DTR_VERSION_NOT_SUPPORTED', $msg);
      break;
  }
}

/**
 * Drush command callback for Drupal 8.
 *
 * @param string $user_rids
 *   Comma-separated list of role rids.
 */
function _drush_drop_the_ripper_d8($user_rids) {
  $start = microtime(TRUE);
  $try_guesses = !(bool) drush_get_option('no-guessing', FALSE);
  $user_uids = drush_get_option('uid', 'all');

  $wordlist = _dtr_get_wordlist();
  if (!$wordlist) {
    $msg = 'Could not find that wordlist.';
    return drush_set_error('DRUSH_MISSING_WORDLIST', $msg);
  }
  $passwords = _dtr_load_wordlist($wordlist);

  $user_query = db_select('users_field_data', 'u')
    ->fields('u')
    ->condition('u.uid', 0, '>');
  $user_query->distinct();
  $conditions = db_or();

  $uids = array();
  if (drush_get_option('restricted', FALSE)) {
    // Ensure User 1 is included as having restricted perms.
    $uids += array(1);
    // This option overrides any supplied rids.
    $user_rids = implode(',', _dtr_get_restricted_roles_d8());
  }
  if ($user_uids != 'all' && $uid_opt = _dtr_get_ids($user_uids)) {
    $uids += $uid_opt;
  }
  $uids = array_unique($uids);
  if (count($uids)) {
    $conditions->condition('u.uid', $uids, 'IN');
  }

  if ($user_rids != 'all') {
    if ($rids = _dtr_get_role_target_ids($user_rids)) {
      $user_query->leftJoin(
        'user__roles',
        'ur',
        'u.uid = ur.entity_id AND ur.bundle = :user',
        array(':user' => 'user')
      );
      $conditions->condition('ur.roles_target_id', $rids, 'IN');
    }
  }

  if ($conditions->count()) {
    $user_query->condition($conditions);
  }

  $users = $user_query->execute()
    ->fetchAll();

  $user_checks = 0;
  $pw_checks = 0;
  foreach ($users as $user) {
    $user_checks++;
    if ($try_guesses) {
      $guesses = _dtr_user_guesses($user);
      foreach ($guesses as $guess) {
        $pw_checks++;
        if (_dtr_check_password_d8($user, $guess)) {
          continue 2; // No need to try the passwords for this user.
        }
      }
    }
    foreach ($passwords as $password) {
      $pw_checks++;
      if (_dtr_check_password_d8($user, $password)) {
        break;
      }
    }
  }
  $finish = microtime(TRUE);
  drush_log(dt('Ran @pc password checks for @uc users in @sec seconds.', array(
    '@pc' => $pw_checks,
    '@uc' => $user_checks,
    '@sec' => sprintf('%.2f', $finish - $start),
  )),
  LogLevel::SUCCESS);
}

/**
 * Check a user password in Drupal 8.
 *
 * @param object $user
 *   A user object.
 *
 * @param string $password
 *   The password to check.
 *
 * @return bool
 *   Whether the password matched.
 */
function _dtr_check_password_d8($user, $password) {
  $hide = (bool) drush_get_option('hide', FALSE);
  $container = \Drupal::getContainer();
  $user_auth = $container->get('user.auth');
  $matched = FALSE;
  drush_log(dt("Check: uid=@uid name=@name for password '@pass'", array(
    '@uid' => $user->uid,
    '@name' => $user->name,
    '@pass' => $password,
  )),
  LogLevel::DEBUG);
  if ($user_auth->authenticate($user->name, $password) === $user->uid) {
    drush_log(dt('Match: uid=@uid name=@name password=@pass', array(
      '@uid' => $user->uid,
      '@name' => $user->name,
      '@pass' => $hide ? 'XXXXXXXXXX' : $password,
    )),
    LogLevel::SUCCESS);
    $matched = TRUE;
  }
  return $matched;
}

/**
 * Get a list of restricted permissions in Drupal 8.
 *
 * @return array
 *   The names of permissions marked as restricted.
 */
function _dtr_get_restricted_permissions_d8() {
  $container = \Drupal::getContainer();
  $all_perms = $container->get('user.permissions')->getPermissions();
  foreach ($all_perms as $name => $perm) {
    if (!empty($perm['restrict access'])) {
      $restricted_perms[] = $name;
    }
  }
  drush_log(dt("Restricted perms: @perms  ", array(
    '@perms' => implode(', ', $restricted_perms),
  )),
  LogLevel::DEBUG);
  return $restricted_perms;
}

/**
 * Get a list of roles with restricted permissions in Drupal 8.
 *
 * @return array
 *   The names of roles with restricted permissions.
 */
function _dtr_get_restricted_roles_d8() {
  $restricted_perms = _dtr_get_restricted_permissions_d8();
  $roleStorage = \Drupal::entityManager()->getStorage('user_role');
  $roles = $roleStorage->loadMultiple();
  $restricted_roles = array();
  foreach ($roles as $name => $role) {
    if ($role->isAdmin() || count(array_intersect($restricted_perms, $role->getPermissions()))) {
      $restricted_roles[] = $name;
    }
  }
  drush_log(dt("Restricted roles: @roles  ", array(
    '@roles' => implode(', ', $restricted_roles),
  )),
  LogLevel::DEBUG);
  return $restricted_roles;
}

/**
 * Drush command callback for Drupal 7.
 *
 * @param string $user_rids
 *   Comma-separated list of role rids.
 */
function _drush_drop_the_ripper_d7($user_rids) {
  $start = microtime(TRUE);
  $try_guesses = !(bool) drush_get_option('no-guessing', FALSE);
  $user_uids = drush_get_option('uid', 'all');
  require_once DRUPAL_ROOT . '/' . variable_get('password_inc', 'includes/password.inc');

  $wordlist = _dtr_get_wordlist();
  if (!$wordlist) {
    $msg = 'Could not find that wordlist.';
    return drush_set_error('DRUSH_MISSING_WORDLIST', $msg);
  }
  $passwords = _dtr_load_wordlist($wordlist);

  $user_query = db_select('users', 'u')
    ->fields('u')
    ->condition('u.uid', 0, '>')
    ->distinct();
  $conditions = db_or();

  $uids = array();
  if (drush_get_option('restricted', FALSE)) {
    // Ensure User 1 is included as having restricted perms.
    $uids += array(1);
    // This argument overrides any supplied rids.
    $user_rids = implode(',', _dtr_get_restricted_roles_d7());
  }
  if ($user_uids != 'all' && $uid_opt = _dtr_get_ids($user_uids)) {
    $uids += $uid_opt;
  }
  $uids = array_unique($uids);
  if (count($uids)) {
    $conditions->condition('u.uid', $uids, 'IN');
  }

  if ($user_rids != 'all') {
    if ($rids = _dtr_get_ids($user_rids)) {
      $user_query->leftJoin('users_roles', 'ur', 'u.uid = ur.uid');
      $conditions->condition('ur.rid', $rids, 'IN');
    }
  }

  if ($conditions->count()) {
    $user_query->condition($conditions);
  }

  $users = $user_query->execute()
    ->fetchAll();

  $user_checks = 0;
  $pw_checks = 0;
  foreach ($users as $user) {
    $user_checks++;
    if ($try_guesses) {
      $guesses = _dtr_user_guesses($user);
      foreach ($guesses as $guess) {
        $pw_checks++;
        if (_dtr_check_password_d7($user, $guess)) {
          continue 2; // No need to try the passwords for this user.
        }
      }
    }
    foreach ($passwords as $password) {
      $pw_checks++;
      if (_dtr_check_password_d7($user, $password)) {
        break;
      }
    }
  }
  $finish = microtime(TRUE);
  drush_log(dt('Ran @pc password checks for @uc users in @sec seconds.', array(
    '@pc' => $pw_checks,
    '@uc' => $user_checks,
    '@sec' => sprintf('%.2f', $finish - $start),
  )),
  LogLevel::SUCCESS);
}

/**
 * Check a user password in Drupal 7.
 *
 * @param object $user
 *   A user object.
 *
 * @param string $password
 *   The password to check.
 *
 * @return bool
 *   Whether the password matched.
 */
function _dtr_check_password_d7($user, $password) {
  $hide = (bool) drush_get_option('hide', FALSE);
  $matched = FALSE;
  drush_log(dt("Check: uid=@uid name=@name for password '@pass'", array(
    '@uid' => $user->uid,
    '@name' => $user->name,
    '@pass' => $password,
  )),
  LogLevel::DEBUG);
  if (user_check_password($password, $user)) {
    drush_log(dt('Match: uid=@uid name=@name password=@pass', array(
      '@uid' => $user->uid,
      '@name' => $user->name,
      '@pass' => $hide ? 'XXXXXXXXXX' : $password,
    )),
    LogLevel::SUCCESS);
    $matched = TRUE;
  }
  return $matched;
}

/**
 * Get a list of restricted permissions in Drupal 7.
 *
 * @return array
 *   The names of permissions marked as restricted.
 */
function _dtr_get_restricted_permissions_d7() {
  $all_perms = module_invoke_all('permission');
  foreach ($all_perms as $name => $perm) {
    if (!empty($perm['restrict access'])) {
      $restricted_perms[] = $name;
    }
  }
  drush_log(dt("Restricted perms: @perms  ", array(
    '@perms' => implode(', ', $restricted_perms),
  )),
  LogLevel::DEBUG);
  return $restricted_perms;
}

/**
 * Get a list of roles with restricted permissions in Drupal 7.
 *
 * @return array
 *   The rids of roles with restricted permissions.
 */
function _dtr_get_restricted_roles_d7() {
  $restricted_perms = _dtr_get_restricted_permissions_d7();
  $role_query = db_select('role_permission', 'rp')
    ->fields('rp', array('rid'))
    ->condition('rp.permission', $restricted_perms, 'IN')
    ->groupBy('rp.rid');
  $rids = $role_query->execute()
    ->fetchCol();
  drush_log(dt("Restricted roles: @roles  ", array(
    '@roles' => implode(', ', $rids),
  )),
  LogLevel::DEBUG);
  return $rids;
}

/**
 * Make a few guesses about a user's password.
 *
 * @param object $user
 *   A (basic) user object.
 *
 * @return array
 *   Guesses at the user's password.
 */
function _dtr_user_guesses($user) {
  $guesses = array();
  $guesses[] = $user->name;
  $guesses[] = $user->name . date('Y');
  $guesses[] = $user->mail;
  if (preg_match('/(.*)@(.*)\..*/', $user->mail, $matches)) {
    $guesses[] = $matches[1]; // Username portion of mail.
    $guesses[] = $matches[2]; // First part of domain.
  }
  return array_unique(array_filter($guesses));
}

/**
 * Parse a wordlist file into an array.
 *
 * @param string $wordlist
 *   Path to the wordlist file.
 *
 * @return array
 *   Candidate passwords.
 */
function _dtr_load_wordlist($wordlist) {
  $passwords = file($wordlist);
  $passwords = array_filter($passwords, '_dtr_wordlist_filter_callback');
  $passwords = array_map('_dtr_trim_newline', $passwords);
  $passwords = array_unique($passwords);

  if (!drush_get_option('all', FALSE)) {
    $top = (int) drush_get_option('top', 25);
    if ($top > 0) {
      $passwords = array_slice($passwords, 0, $top);
    }
  }

  return $passwords;
}

/**
 * Callback for wordlist array filtering; removes comments.
 *
 * @param string $line
 *   An item from a wordlist.
 *
 * @return bool
 *   FALSE if the line is a comment.
 */
function _dtr_wordlist_filter_callback($line) {
  return (strpos($line, '#!comment:') !== 0);
}

/**
 * Callback for wordlist array trimming; remove only trailing newlines.
 *
 * @param string $line
 *   An item from a wordlist.
 *
 * @return string
 *   Candidate password with trailing newline removed.
 */
function _dtr_trim_newline($line) {
  // Note that double quotes are necessary for the whitespace characters.
  return rtrim($line, "\r\n");
}

/**
 * Parse the supplied (u|r)ids option.
 *
 * @param string $ids
 *   The user-supplied list of uids or rids.
 *
 * @return array|bool
 *   Array of numeric ids, or FALSE if none were valid.
 */
function _dtr_get_ids($ids) {
  $ids = explode(',', $ids);
  $ids = array_map('trim', $ids);
  $ids = array_filter($ids, 'is_numeric');
  return (empty($ids) ? FALSE : $ids);
}

/**
 * Parse the supplied roles option.
 *
 * @param string $user_rids
 *   The user-supplied list of roles.
 *
 * @return array|bool
 *   Array of roles_target_id's, or FALSE if none were valid.
 */
function _dtr_get_role_target_ids($user_rids) {
  $rids = explode(',', $user_rids);
  $rids = array_map('trim', $rids);
  // todo: filter target_id (based on ASCII character set)
  return (empty($rids) ? FALSE : $rids);
}

/**
 * Process the --wordlist option, or use a default.
 *
 * @return string|bool
 *   Path to the wordlist, or FALSE if it does not exist.
 */
function _dtr_get_wordlist() {
  $wordlist = drush_get_option('wordlist', 'default');
  if ($wordlist == 'default') {
    $wordlist = __DIR__ . DIRECTORY_SEPARATOR . 'password';
  }
  else {
    // Custom wordlist; use all entries unless a --top option was supplied.
    if (((int) drush_get_option('top', 0)) == 0) {
      drush_set_option('all', TRUE);
    }
  }
  if (!file_exists($wordlist)) {
    $wordlist = FALSE;
  }
  return $wordlist;
}
