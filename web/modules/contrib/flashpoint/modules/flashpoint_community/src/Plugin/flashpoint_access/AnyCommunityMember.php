<?php

namespace Drupal\flashpoint_community\Plugin\flashpoint_access;

use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\flashpoint\FlashpointAccessMethodInterface;


/**
 * @FlashpointAccessMethod(
 *   id = "any_community_member",
 *   label = @Translation("Member of any community"),
 * )
 */
class AnyCommunityMember extends PluginBase implements FlashpointAccessMethodInterface {

  /**
   * @return string
   *   A string description.
   */
  public function description()
  {
    return $this->t('The user must be a member of any community.');
  }

  /**
   * @param $group
   * @param AccountInterface $account
   *
   * Determines whether someone has access to enroll in a course
   * @return boolean
   */
  public static function checkAccess($group, AccountInterface $account) {
    $memberships = \Drupal::service('group.membership_loader')->loadByUser($account);
    foreach ($memberships as $membership) {
      $group_type = $membership->getGroup()->bundle();
      if ($group_type === 'flashpoint_community') {
        return TRUE;
      }
    }
    return FALSE;
  }

}
