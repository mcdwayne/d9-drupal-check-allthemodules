<?php
/**
 * @file
 * Provides Drupal\fasttoggle\SettingGroupInterface.
 */

namespace Drupal\fasttoggle\Plugin\SettingGroup;

use Drupal\fasttoggle\Plugin\SettingObject\SettingObjectInterface;

/**
 * An interface for groups of settings on an object.
 */
interface SettingGroupInterface extends SettingObjectInterface {

  /**
   * Get an array of sitewide setting form elements for this object type.
   *
   * @param $config
   *   The configuration storage.
   *
   * @return array
   *   Render array for the sitewide settings.
   */
  public static function getSitewideSettingFormElements($config);

  /**
   * Write access control check for the group of settings.
   *
   * @return bool
   *   Whether the user is permitted to modify settings on this group of settings.
   */
  public function mayEditGroup();

  /**
   * Return whether this setting group includes the provided field definition.
   *
   * @param $definition
   *   The field definition for which a match should be sought.
   *
   * @return string
   *   The name of the group plugin to use for this field definition.
   */
  public static function groupMatches($definition);
}
