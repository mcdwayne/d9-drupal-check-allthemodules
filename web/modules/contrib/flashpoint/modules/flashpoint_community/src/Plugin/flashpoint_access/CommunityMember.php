<?php

namespace Drupal\flashpoint_community\Plugin\flashpoint_access;

use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\flashpoint\FlashpointAccessMethodInterface;


/**
 * @FlashpointAccessMethod(
 *   id = "community_member",
 *   label = @Translation("Member of a selected community"),
 * )
 */
class CommunityMember extends PluginBase implements FlashpointAccessMethodInterface
{

  /**
   * @return string
   *   A string description.
   */
  public function description()
  {
    return $this->t('The user must be a member of a specified community.');
  }

  /**
   * @param $group
   * @param AccountInterface $account
   *
   * Determines whether someone has access to enroll in a course
   * @return boolean
   */
  public static function checkAccess($group, AccountInterface $account) {

    $allowed_ids = [];
    if ($group->hasField('field_eligible_communities')) {
      foreach ($group->get('field_eligible_communities')->getValue() as $item) {
        $allowed_ids[] = $item['target_id'];
      }
    }

    $memberships = \Drupal::service('group.membership_loader')->loadByUser($account);

    foreach ($memberships as $membership) {
      $group_type = $membership->getGroup()->bundle();
      if ($group_type === 'flashpoint_community' && in_array($membership->getGroup()->id(), $allowed_ids)) {
        return TRUE;
      }
    }
    return FALSE;
  }

}