<?php

namespace Drupal\multilingual_login_redirect\Entity;

use Drupal;
use Drupal\user\Entity\Role;

/**
 * Class to manage the MultilingualRedirect Entity.
 */
class MultilingualRedirect {
  private static $MlrConfigSettingsName = 'multilingual_login_redirect.settings';

  /**
   * Get redirect value.
   *
   * @param string $field
   *   A string containing the name of the setting to be retrieved.
   *
   * @return string
   *   A string containing the setting value
   */
  public static function getRedirect($field) {
    return Drupal::config(self::$MlrConfigSettingsName)->get($field);
  }

  /**
   * Set redirect value.
   *
   * @param string $field
   *   A string containing the name of the setting to be saved.
   * @param string $url
   *   A string containing the value to be saved.
   *
   * @return mixed
   *   Result of save configuration
   */
  public static function setRedirect($field, $url) {
    return Drupal::configFactory()->getEditable(self::$MlrConfigSettingsName)->set($field, $url)->save();
  }

  /**
   * Checking if form field is a Mlr Redirect.
   *
   * @param string $redirect
   *   A string containing the redirect value.
   *
   * @return string
   *   A string describing the type of redirect
   */
  public static function getRedirectType($redirect) {
    return (substr($redirect, 0, 5) == 'node:') ? 'node' : 'url';
  }

  /**
   * Return node id from node field value.
   *
   * @param string $field_value
   *   A string containing the node redirect value.
   *
   * @return string
   *   A string containing the node number.
   */
  public static function getNodeIdFromNodeField($field_value) {
    if (!self::getRedirectType($field_value) == 'node') {
      return FALSE;
    }
    return substr($field_value, 5);
  }

  /**
   * Checking if url is a valid Mlr url.
   *
   * @param string $url
   *   A string containing the url value.
   *
   * @return bool
   *   True if url is valid or false if url is not valid.
   */
  public static function isValidUrl($url) {
    if (
    // Absolute path.
      preg_match('/^https?:\/\/[a-z0-9-]+/i', $url) ||
    // Relative path.
      preg_match('/(\/)[a-z0-9-]?+/i', $url) ||
    // Node number.
      preg_match('/(node:)[0-9]+/i', $url) ||
    // Empty string.
      trim($url) === ''
    ) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Checking if form field is a Mlr Redirect.
   *
   * @param string $field
   *   A string containing the field name.
   *
   * @return bool
   *   True if $field is a redirect field, otherwise return false.
   */
  public static function submissionIsMlr($field) {
    return substr($field, 0, 5) == 'mlr_d';
  }

  /**
   * Check if redirect for this role and user exist in db.
   *
   * @param string $redirect_value
   *   A string containing the value of the redirect.
   *
   * @return bool
   *   True if the value exist in settings, otherwise return false
   */
  public static function redirectIsRegistered($redirect_value) {
    return ($redirect_value && !empty($redirect_value) && $redirect_value != '');
  }

  /**
   * Sanitize the role array to be passed the the js file.
   *
   * @param array $roles_array
   *   Array containing a list of roles.
   *
   * @return array
   *   Array containing the roles that have to be passed to the javascript file.
   */
  public static function sanitizeRolesArray(array $roles_array) {
    $excluded_roles = [
      'anonymous',
    ];
    foreach ($roles_array as $role => $role_info) {
      if (in_array($role, $excluded_roles)) {
        unset($roles_array[$role]);
      }
    }
    return $roles_array;
  }

  /**
   * Return the Drupal roles for this website.
   *
   * @return array
   *   Array containing the roles available in the website.
   */
  public static function getDrupalRoles() {
    return Role::loadMultiple();
  }

  /**
   * Return roles for this user sorted by weight.
   *
   * @return array
   *   An array containing the roles for the current user sorted by weight
   */
  public static function getSortedCurrentUserRolesByWeight() {
    $roles = Drupal::currentUser()->getRoles();
    $roles_weight_array = [];
    foreach ($roles as $role_name) {
      $weight = Role::load($role_name)->getWeight();
      $roles_weight_array[$weight] = $role_name;
    }
    ksort($roles_weight_array);
    return $roles_weight_array;
  }

}
