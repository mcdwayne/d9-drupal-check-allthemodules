#!/usr/bin/php
<?php

/**
 * @file
 * This script manages stored passwords. Only the root user should have access
 * to this script and the database used to store passwords.
 *
 * Usage: stored_passwords.php [OPTIONS]...
 *
 * Options:
 *   username=STRING  User identity. When not given, the delete op removes all
 *                    users from the realm.
 *   realm=STRING     Realm. Defaults to hostname.
 *   pass=STRING  User password.
 *   op=STRING        Create or delete. By default, an existing user will be
 *                    updated.
 */

/**
 * Set error level to report only fatal errors.
 */

error_reporting(E_ERROR);

/**
 * Get configuration file variables.
 */
require 'digest_md5.conf.php';

/**
 * Get command line variables.
 */
$edit = array();
array_shift($argv);
foreach ($argv as $arg) {
  list($key, $value) = explode('=', $arg, 2);
  $edit[$key] = trim($value);
}

/**
 * Output a help message.
 */
if (!empty($edit)) {
  foreach (array('-h', '--help', '-help', '-?', '/?', '?') as $arg) {
    if (in_array($arg, $argv)) {
      _stored_passwords_help();
    }
  }
}
else {
  _stored_passwords_help();
}

/**
 * Make sure realm is set.
 */
$uname = posix_uname();
$edit['realm'] = isset($edit['realm']) ? $edit['realm'] : $uname['nodename'];

/**
 * Open a database connection.
 */
$cwd = getcwd();
chdir($drupal);

/**
 * include autoload.php to autoload all the classes used in bootstrap.inc and database.inc.
 */
include_once('./core/vendor/autoload.php');

require "./core/includes/bootstrap.inc";
require_once "./core/includes/database.inc";
use Drupal\Core\Site\Settings;
/**
 * initilize settings for settings to be read from settings.php.
 */

//$conf_path = conf_path();
Settings::initialize($edit['site_path']);

db_set_active();
chdir($cwd);
_securesite_schema();

/**
 * Execute command.
 */
_stored_passwords_manage($edit);

/**
 * Work with stored passwords.
 * @param $edit
 *   An array of data with the following keys:
 *   - username: User name
 *   - realm: Site realm
 *   - pass: User password
 *   - op: The operation to be performed. If none is given, an existing user will be updated.
 * @return
 *   None.
 */
function _stored_passwords_manage($edit) {
  $op = isset($edit['op']) ? $edit['op'] : NULL;
  switch ($op) {
    case 'create':
      if (db_query("SELECT COUNT(*) FROM `securesite_passwords` WHERE name = :name AND realm = :realm", array(':name' => $edit['username'], ':realm' => $edit['realm']))->fetchField() == 0) {
        $result = db_query("INSERT INTO `securesite_passwords` (name, realm, pass) VALUES (:name, :realm, :pass)", array(':name' => $edit['username'], ':realm' => $edit['realm'], ':pass' => $edit['pass']));
        $output = $result === FALSE ? "Failed to add $edit[username] to $edit[realm]." : "Added $edit[username] to $edit[realm].";
      }
      else {
        unset($edit['op']);
        $output = _stored_passwords_manage($edit);
      }
      break;
    case 'delete':
      if (isset($edit['username'])) {
        if (db_query("SELECT COUNT(*) FROM `securesite_passwords` WHERE name = :name AND realm = :realm", array(':name' => $edit['username'], ':realm' => $edit['realm']))->fetchField() == 0) {
          $output = "$edit[username] not found in $edit[realm].";
        }
        else {
          $result = db_query("DELETE FROM `securesite_passwords` WHERE name = :name AND realm = :realm", array(':name' => $edit['username'], ':realm' => $edit['realm']));
          $output = $result === FALSE ? "Failed to remove $edit[username] from $edit[realm]." : "Removed $edit[username] from $edit[realm].";
        }
      }
      else {
        $result = db_query("DELETE FROM `securesite_passwords` WHERE realm = :realm", array('realm' => $edit['realm']));
        $output = $result === FALSE ? "Failed to remove users from $edit[realm]." : "Removed users from $edit[realm].";
      }
      break;
    default:
      if (db_query("SELECT COUNT(*) FROM `securesite_passwords` WHERE name = :name AND realm = :realm", array(':name' => $edit['username'], ':realm' => $edit['realm']))->fetchField() == 0) {
        $output = "$edit[username] not found in $edit[realm].";
      }
      else {
        $result = db_query("UPDATE `securesite_passwords` SET pass = :pass WHERE name = :name AND realm = :realm", array(':pass' => $edit['pass'], ':name' => $edit['username'], ':realm' => $edit['realm']));
        $output = $result === FALSE ? "Failed to update $edit[username] in $edit[realm]." : "Updated $edit[username] in $edit[realm].";
      }
      break;
  }
  exit("$output\n");
}

/**
 * Display help message.
 */
function _stored_passwords_help() {
  exit('Usage: stored_passwords.php [OPTIONS]...'."\n".
       "\n".
       'Options:'."\n".
       '  username=STRING    User identity. When not given, the delete op removes all'."\n".
       '                     users from the realm.'."\n".
       '  realm=STRING       Realm. Defaults to hostname.'."\n".
       '  pass=STRING    User password.'."\n".
       '  op=STRING          Create or delete. By default, an existing user identity'."\n".
       '                     will be updated.'."\n".
       "\n");
}
