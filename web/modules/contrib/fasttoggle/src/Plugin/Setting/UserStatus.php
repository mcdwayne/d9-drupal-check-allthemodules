<?php
/**
 * @file
 * Fasttoggle User Status
 */

namespace Drupal\fasttoggle\Plugin\Setting;

require_once __DIR__ . '/../../../fasttoggle.inc';

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Field\FieldItemList;
use Drupal\fasttoggle\Plugin\Field\FieldFormatter\BooleanFormatter;
use Drupal\fasttoggle\Plugin\SettingGroup\UserCore;
use Drupal\fasttoggle\Plugin\Setting\SettingTrait;

/**
 * Abstract interface for settings. Plugin strings are used for quick
 * filtering without the need to instantiate the class.
 *
 * @Plugin(
 *   id = "user_status",
 *   entityType = "user",
 *   name = "status",
 *   description = "Status <small>(active/blocked)</small>",
 *   group = "user_core",
 *   weight = 10,
 *   default = false,
 *   base_formatter = "Drupal\fasttoggle\Plugin\Field\FieldFormatter\BooleanFormatter",
 *   labels = {
 *     FASTTOGGLE_LABEL_ACTION = {
 *       "0" = @Translation("activate"),
 *       "1" = @Translation("block"),
 *     },
 *     FASTTOGGLE_LABEL_STATUS = {
 *       "0" = @Translation("blocked"),
 *       "1" = @Translation("activate"),
 *     },
 *   },
 *   attributes = {
 *     "status" = "Status",
 *   },
 * )
 */
class UserStatus extends UserCore implements SettingInterface {

  use SettingTrait;

  /**
   * Access control function.
   *
   * @param $user
   *   The user against which to check (un)publish permission.
   *
   * @return boolean
   *   Whether the user is allowed to (un)publish the user.
   */
  public function mayEditSetting() {
    $config = \Drupal::config('fasttoggle.settings');
    $sitewide = $config->get('user_core_status');

    $user = \Drupal::currentUser();
    return AccessResult::allowedIfHasPermission($user, "override user blocked option")
      ->andIf(AccessResult::allowedIf($sitewide));
  }

  /**
   * Return whether this setting matches the provided field definition.
   *
   * @param $definition
   *   The field definition for which a match is being sought.
   *
   * @return boolean
   *   Whether this plugin handles the definition.
   */
  public static function matches($definition) {
    $has_get = is_callable(array($definition, 'get'));
    $entity = $has_get ? $definition->get('entity_type') : $definition->getProvider();
    return ($entity == 'user' && $definition->getName() == 'status');
  }

}
