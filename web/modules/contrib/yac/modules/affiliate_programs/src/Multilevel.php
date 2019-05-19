<?php

namespace Drupal\yac_affiliate_programs;

use Drupal\yac_affiliate\Affiliate;
use Drupal\yac_referral\ReferralHandlers;

/**
 * Multilevel class.
 *
 * Manages the Multivel functionality for Yac Affiliate Programs.
 */
class Multilevel {

  /**
   * Calculates the depth of the subscription.
   *
   * Minimun depth level is 0. Every other user that match the criteria will
   * increase the depth level by one.
   *
   * @param string $user_code
   *   The code that identifies the user.
   *
   * @return int
   *   A number that represents the depth of the subscription.
   */
  private function calcDepth(string $user_code) {
    $users = Affiliate::cleanUsersList();
    $level = 0;
    foreach ($users as $user) {
      if (ReferralHandlers::hasReferralCode($user)) {
        if (
          $user->field_referral_code->value === $user_code ||
          $user->field_referent_code->value === $user_code
          ) {
          $level++;
        }
      }
    }
    return $level;
  }

}
