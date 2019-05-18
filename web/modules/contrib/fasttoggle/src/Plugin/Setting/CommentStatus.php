<?php
/**
 * @file
 * Fasttoggle Comment Status
 */

namespace Drupal\fasttoggle\Plugin\Setting;

require_once __DIR__ . '/../../../fasttoggle.inc';

use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Field\FieldItemList;
use Drupal\fasttoggle\Plugin\Field\FieldFormatter\BooleanFormatter;
use Drupal\fasttoggle\Plugin\SettingGroup\CommentCore;
use Drupal\fasttoggle\Plugin\Setting\SettingTrait;

/**
 * Abstract interface for settings. Plugin strings are used for quick
 * filtering without the need to instantiate the class.
 *
 * @Plugin(
 *   entityType = "comment",
 *   name = "status",
 *   description = "Status <small>(published/unpublished)</small>",
 *   id = "comment_status",
 *   group = "comment_core",
 *   weight = 0,
 *   default = false,
 *   base_formatter = "Drupal\fasttoggle\Plugin\Field\FieldFormatter\BooleanFormatter",
 *   attributes = {
 *    "status" = "Status",
 *   },
 *   labels = {
 *     FASTTOGGLE_LABEL_ACTION = {
 *       0 = @Translation("publish"),
 *       1 = @Translation("unpublish"),
 *     },
 *     FASTTOGGLE_LABEL_STATUS = {
 *       0 = @Translation("not published"),
 *       1 = @Translation("published"),
 *     },
 *   },
 * )
 */
class CommentStatus extends CommentCore implements SettingInterface {

  use SettingTrait;

  /**
   * Access control function.
   *
   * @param $comment
   *   The comment against which to check (un)publish permission.
   *
   * @return boolean
   *   Whether the user is allowed to (un)publish the comment.
   */
  public function mayEditSetting() {
    $user = \Drupal::currentUser();
    return AccessResult::allowedIfHasPermission($user,"override comment published option")
      ->orIf(AccessResult::allowedIfHasPermission($user,'moderate comments'));
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
    return ($entity == 'comment' && $definition->getName() == 'status');
  }

}
