<?php

namespace Drupal\yac_affiliate;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\Entity\User;

/**
 * Class Affiliate.
 *
 * Useful methods to manage and handle affiliates..
 *
 * @package Drupal\yac_affiliate\Affiiiate
 * @author Alessandro Cereda <alessandro@geekworldesign.com>
 * @group yac_affiliate
 */
class Affiliate {

  use StringTranslationTrait;

  /**
   * Import config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * ReferralHandlers constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The imported config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * Adds the affiliate role to a User entity.
   *
   * @param \Drupal\user\Entity\User $user
   *   The User that will gain the affiliate role.
   */
  private function affiliateAddRole(User $user) {
    $user->addRole('affiliate');
    return $user->save();
  }

  /**
   * Removes the affiliate role from a User entity.
   *
   * @param \Drupal\user\Entity\User $user
   *   The User that will lose the affiliate role.
   */
  private function affiliateRemoveRole(User $user) {
    $user->removeRole('affiliate');
    return $user->save();
  }

  /**
   * Gets all the affiliates registered on your site.
   *
   * @return array
   *   An array of user with the affiliates role.
   */
  private function affilatesLoadAll() {
    $affiliates = [];
    $users = self::cleanUsersList();
    foreach ($users as $user) {
      if ($this->isAffiliate($user)) {
        $affiliates[] = $user;
      }
    }
    return $affiliates;
  }

  /**
   * Check if the given profile can be affiliate to another user.
   *
   * @param \Drupal\user\Entity\User|UserSession $account
   *   The User Interface used to retrieve information.
   *
   * @return bool
   *   A boolean that indicates if the user can affiliate to another.
   */
  private function canAffiliate($account) {
    $user = User::load($account->id());
    // If the referent code of the User enetity is not empty or the user has
    // referent role return FALSE.
    if ($user->hasRole('referent') || !empty($user->field_referent_code->value)) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Manage the User entities list used in ReferralHandlars.
   *
   * @return \Drupal\user\Entity\User[]
   *   An array of User entities.
   */
  public static function cleanUsersList() {
    $include_admin = self::includeAdmin();
    $users = User::loadMultiple();
    if (!$include_admin) {
      foreach ($users as $i => $key) {
        if ($users[$i]->hasRole('admin')) {
          unset($users[$i]);
        }
      }
    }
    return $users;
  }

  /**
   * Choose if the admin is part of referral group or not.
   *
   * @return bool
   *   TRUE if the admin is part of affiliate programs.
   */
  public static function includeAdmin() {
    $config_factory = \Drupal::configFactory();
    $config = $config_factory->get('yac_affiliate_programs.configuration');
    $include_admin = $config->get('include_admin');
    return $include_admin;
  }

}
