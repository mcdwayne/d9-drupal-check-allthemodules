<?php
/**
 * @file
 * Fasttoggle User Role
 */

namespace Drupal\fasttoggle\Plugin\SettingGroup;

require_once __DIR__ . '/../../../fasttoggle.inc';

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Plugin\PluginBase;
use Drupal\fasttoggle\Plugin\SettingObject\user;

/**
 * Abstract interface for settings. Plugin strings are used for quick
 * filtering without the need to instantiate the class.
 *
 * @Plugin(
 *   id = "user_roles",
 *   entityType = "user",
 *   title = @Translation("Roles"),
 *   description = FALSE,
 *   weight = 100,
 *   fieldset = FALSE,
 * )
 */
class UserRoles extends user implements SettingGroupInterface {

  /**
   * Return whether this setting matches the provided field definition.
   *
   * @param $definition
   *   The field definition for which a match is being sought.
   *
   * @return boolean
   *   Whether this plugin handles the definition.
   */
  public static function groupMatches($definition) {
    return true;
  }

  /**
   * Access.
   *
   * @return bool
   *   Whether the user is permitted to modify settings on this comment.
   */
  public function mayEditGroup() {
    return AccessResult::allowed();
  }

}
