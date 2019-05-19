<?php

/**
 * @file
 * Contains \Drupal\sms_ui\UserGroupListManager.
 */

namespace Drupal\sms_ui;

use Drupal\Core\Session\AccountInterface;
use Drupal\file\FileInterface;
use Drupal\user\UserDataInterface;

/**
 * Manages group lists for individual users.
 */
class UserGroupListManager {

  /**
   * Holds information on users' group lists.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  public function __construct(UserDataInterface $user_data) {
    $this->userData = $user_data;
  }

  /**
   * Adds a file to the group list files belonging to a user.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user.
   * @param \Drupal\file\FileInterface $file
   *   The file to be added. It should have been uploaded and saved already.
   */
  public function addGroupList(AccountInterface $user, FileInterface $file) {
    $list = $this->getGroupList($user);
    $list[$file->id()] = $file->label();
    $this->userData->set('sms_ui', $user->id(), 'group_list', $list);
  }

  /**
   * Gets available group lists for a specific user.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user for which the group list is being retrieved.
   *
   * @return array
   */
  public function getGroupList(AccountInterface $user) {
    return $this->userData->get('sms_ui', $user->id(), 'group_list') ?: [];
  }

  /**
   * Removes a named group list from the user's record
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user.
   * @param string $name
   *   The name of the group list being removed.
   */
  public function removeGroupList(AccountInterface $user, $name) {
    $list = $this->getGroupList($user);
    unset($list[$name]);
    $this->userData->set('sms_ui', $user->id(), 'group_list', $list);
  }

  /**
   * Clears all the group lists belonging to a user.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user.
   */
  public function clearGroupList(AccountInterface $user) {
    $this->userData->delete('sms_ui', $user->id(), 'group_list');
  }

}
