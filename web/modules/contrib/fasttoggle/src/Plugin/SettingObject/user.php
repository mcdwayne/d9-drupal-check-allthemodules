<?php
/**
 * @file
 * Fasttoggle Managed Node
 */

namespace Drupal\fasttoggle\Plugin\SettingObject;

require_once __DIR__ . "/AbstractSettingObject.php";

use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\fasttoggle\ObjectInterface;
use Drupal\fasttoggle\Controller\FasttoggleController;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheableMetadata;

/**
 * Class for managing users.
 *
 * @Plugin(
 *   id = "user",
 *   title = @Translation("Users"),
 *   description = @Translation("Select which options for fast toggling of user settings are available."),
 *   weight = 20,
 * )
 */
class user extends AbstractSettingObject implements SettingObjectInterface {

  /**
   * Object ID.
   *
   * @return integer
   *   The unique ID of this instance of the object.
   */
  public function get_id() {
    return $this->object->uid;
  }

  /**
   * User name.
   *
   * @return string
   *   The user's name.
   */
  public function get_title() {
    return $this->object->username;
  }

  /**
   * Save function.
   */
  public function save() {
    $this->object->save();
  }

  /**
   * Matches an object?
   */
  public function objectMatches($object) {
    return ($object instanceof \Drupal\user\Entity\User);
  }

  /**
   * Access.
   *
   * @param &$elements
   *   The render array, to which caching info should be added.
   *
   * @return AccessResult
   *   AccessResult (cacheability info and the outcome).
   */
  public function mayEditEntity() {
    $user = \Drupal::currentUser();

    if ($this->object->id() == 1) {
      $config = FasttoggleController::getConfig();
      $result = AccessResult::allowedIf($config->get('user_allow_block_user1'))
        ->addCacheableDependency($this->object);

      $cacheability = new CacheableMetadata();
      $cacheability->setCacheTags(['user_allow_block_user1']);
      $result->addCacheableDependency($cacheability);
      return $result;
    }

    return AccessResult::allowedIf($user->hasPermission('administer users'))
      ->cachePerUser();
  }

  /**
   * Return a list of settings for this object type.
   *
   * @param $config
   *   The configuration storage.
   *
   * @return array
   *   Settings that can be modified.
   */
  public static function getSitewideSettingFormElements($config) {
    return [
      'user_allow_block_user1' => [
        '#type' => 'checkbox',
        '#title' => t("Allow user 1's account to be blocked using Fasttoggle."),
        '#default_value' => $config->get('user_allow_block_user1'),
        '#weight' => -100,
      ]
    ];
  }

}
