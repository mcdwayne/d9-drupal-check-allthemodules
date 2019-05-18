<?php
/**
 * @file
 * Fasttoggle Managed Comment
 */

namespace Drupal\fasttoggle\Plugin\SettingObject;

require_once __DIR__ . "/AbstractSettingObject.php";

use Drupal\Core\Plugin\PluginBase;
use Drupal\fasttoggle\ObjectInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Class for managing comments.
 *
 * @Plugin(
 *   id = "comment",
 *   title = "Comments",
 *   description = @Translation("Select which options for fast toggling of comment settings are available."),
 *   weight = 10,
 * )
 */
class comment extends AbstractSettingObject implements SettingObjectInterface {

  /**
   * Object ID.
   *
   * @return integer
   *   The unique ID of this instance of the object.
   */
  public function get_id() {
    return $this->object->cid;
  }

  /**
   * Comment title.
   *
   * @return integer
   *   The title of the comment.
   */
  public function get_title() {
    return $this->object->title;
  }

  /**
   * Save function.
   */
  public function save() {
    comment_save($this->object);
  }

  /**
   * Matches an object?
   */
  public function objectMatches($object) {
    return ($object instanceof \Drupal\comment\Entity\Comment);
  }

  /**
   * Access.
   *
   * @param $object
   *   The object for which update access is being checked.
   *
   * @return bool
   *   Whether the user is permitted to modify settings on this comment.
   */
  public function mayEditEntity() {
    $user = \Drupal::currentUser();
    return AccessResult::forbiddenIf(!$user->hasPermission('administer comments'))
      ->cachePerUser();
  }

}
