<?php

namespace Drupal\yac_referral;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\Entity\User;

/**
 * Class ReferralHandlers.
 *
 * Validate and handle the user's request for affiliation.
 *
 * @package Drupal\yac_referral\ReferralHandlers
 * @author Alessandro Cereda <alessandro@geekworldesign.com>
 * @group yac_referral
 */
class ReferralHandlers {

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
   * Count the members of a user's referral network.
   *
   * Uses a given referral code to retrieve the members of a user's network.
   *
   * @param \Drupal\user\Entity\User[] $users
   *   An array of User entities.
   * @param string $referral_code
   *   The code we are using as key to retrieve the network members.
   *
   * @return int
   *   An integer that rapresents the members of the network associated with
   *   the given referral code.
   */
  private function countMembers(array $users, string $referral_code) {
    $members = 0;
    foreach ($users as $user) {
      if ($user->field_referent_code->value === $referral_code) {
        $members++;
      }
    }
    return $members;
  }

  /**
   * Gets the referral code associated with the given user id.
   *
   * @param int $uid
   *   The uid of the user whose information we are retrieving.
   *
   * @return array
   *   A renderable array that show a Drupal message using the method property.
   */
  private function getUserCode(int $uid) {
    $user = User::load($uid);
    $code = $user->field_referral_code->value;
    if (isset($user->field_referral_code)) {
      if (NULL !== $code) {
        return $code;
      }
      else {
        return [
          '#method' => drupal_set_message(
            $this->t('It seems that you don`t have a referral code. Please contact site administrator'), 'error'
          ),
        ];
      }
    }
    else {
      return [
        '#mehod' => drupal_set_message(
          $this->t('Field referral code is not set for user id: @id', [
            '@id' => $uid,
          ]), 'error'),
      ];
    }
  }

  /**
   * Gets all the member of a referral network.
   *
   * All the users that have a profile linked to the given user id through
   * the referent code.
   *
   * @param int $uid
   *   The uid of the user whose network we are retrieving.
   *
   * @return array
   *   An array of user that belongs to the given user uid network.
   */
  public function getUserNetwork(int $uid) {
    $affiliates = $this->affilatesLoadAll();
    $user_code = $this->getUserCode($uid);
    $network = [];
    foreach ($affiliates as $affiliate) {
      if ($affiliate->field_referent_code->value === $user_code) {
        $network[] = $affiliate;
      }
    }
    return $network;
  }

  /**
   * Check if the given profile already has a referral code associated to it.
   *
   * @param \Drupal\user\Entity\User|UserSession $account
   *   The User Interface used to retrieve information.
   *
   * @return bool
   *   A boolean that indicates if the user referral code field has value.
   */
  public static function hasReferralCode($account) {
    $user = User::load($account->id());
    if (!empty($user->field_referral_code->value)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Choose if the admin is part of referral group or not.
   *
   * @return bool
   *   TRUE if admin is part of affiliate programs.
   */
  public static function includeAdmin() {
    $config_factory = \Drupal::configFactory();
    $config = $config_factory->get('yac_affiliate_programs.configuration');
    $include_admin = $config->get('include_admin');
    return $include_admin;
  }

  /**
   * Check if the given profile is a customer one.
   *
   * @param \Drupal\user\Entity\User|UserSession $account
   *   The User Interface used to retrieve information.
   *
   * @return bool
   *   A boolean that indicates if the user referral code field has value.
   */
  public static function isAffiliate($account) {
    $user = User::load($account->id());
    // If the referral code of the User enetity is not empty or the user has
    // affiliate role return TRUE.
    if ($user->hasRole('affiliate') || self::hasReferralCode($account)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Determine if a customer is eligible or not for the affiliation.
   *
   * @param \Drupal\user\Entity\User|UserSession $user
   *   The user entity whose eligibilty we are checking.
   * @param string $affiliation_code
   *   The affiliation code whose validity we are checking.
   *
   * @return bool
   *   A boolean that indicates if the given User entity is eligible or not.
   */
  private function isEligible($user, $affiliation_code) {
    /** @var bool $canAffiliate */
    $canAffiliate = $this->canAffiliate($user);
    /** @var bool $validCode */
    $validCode = $this->validCode($affiliation_code);
    return $canAffiliate && $validCode ? TRUE : FALSE;
  }

  /**
   * Check if the user has the referent role.
   *
   * @param \Drupal\user\Entity\User $user
   *   The User entity used to perform the check.
   *
   * @return bool
   *   A boolean that indicates if the user has the referent role or not.
   */
  private function isReferent(User $user) {
    return !$user->hasRole('referent') || !empty($user->field_referent_code->value) ? FALSE : TRUE;
  }

  /**
   * Creates the user network table for the given user id.
   *
   * @param int|string $uid
   *   The uid of the user we are creating table for.
   *
   * @return array
   *   Renderable array.
   */
  public function networkTable($uid) {
    /** @var array $table_header */
    $table_header = ['User', 'Member since'];
    $rows = $this->networkTableRows($uid);
    if (!empty($rows)) {
      return [
        '#theme' => 'table',
        '#header' => $table_header,
        '#rows' => $rows,
      ];
    }
    else {
      return [
        '#markup' => $this->t('Your network seems to be empty! Start building it now.'),
      ];
    }
  }

  /**
   * Populates the array whose data is shown on the user referral network table.
   *
   * @param int|string $uid
   *   The uid of the user whose table rows we are building.
   *
   * @return array
   *   An array containing the table rows data.
   */
  private function networkTableRows($uid) {
    $network = $network = $this->getUserNetwork($uid);
    $rows = [];
    foreach ($network as $member) {
      $member_account = $member->getOwner();
      $member_array = [
        $member_account->getDisplayName(),
        $member_account->getCreatedTime(),
      ];
      $rows[] = $member_array;
    }
    return $rows;
  }

  /**
   * Set the referent role to the user that corresponds to referral code.
   *
   * @param \Drupal\user\Entity\User $user
   *   The User Entity for whom we are going to retrieve the referent.
   *
   * @return void|null
   *   Returns NULL is no user correspond to the given code.
   */
  private function setReferent(User $user) {
    $affiliates = $this->affilatesLoadAll();
    foreach ($affiliates as $affiliate) {
      if ($user->field_referent_code->value === $affiliate->field_referral_code->value && !$this->isReferent($affiliate)) {
        $affiliate->addRole('referent');
        $affiliate->save();
        return $affiliate;
      }
    }
    return NULL;
  }

  /**
   * Create system messages.
   *
   * Inform users about the status of their registration, depends on the
   * validity of the affiliation code provided.
   *
   * @param string $type
   *   The type of message we are going to create.
   *
   * @return array|null
   *   A renderable array that attach drupal_set_message() function to method.
   *   Returns null if $type is not valid.
   */
  private function statusMessage(string $type) {
    $config = $this->configFactory->get('yac_referral.configuration');
    switch ($type) {
      case "registration_complete":
        /** @var string $message */
        $message = $config->get('confirm_msg');
        return [
          "#markup" => drupal_set_message($message, "status"),
        ];

      case "already_member":
        /** @var string $message */
        $message = $config->get('already_member_msg');
        return [
          "#markup" => drupal_set_message($message, "error"),
        ];

      case "invalid_code":
        /** @var string $message */
        $message = $config->get('invalid_msg');
        return [
          "#markup" => drupal_set_message($message, "error"),
        ];

      default:
        return NULL;

    }
  }

  /**
   * Register the referent code inside the current customer's profile.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $accountProxy
   *   The account proxy interface we are submitting.
   * @param string $code
   *   The affiliation code that will be associated with the given account.
   *
   * @return array|EntityInterface|User
   *   The return value of submitAffiliate method.
   *
   * @throws \Drupal\Core\TypedData\Exception\ReadOnlyException
   */
  public function submitAffiliate(AccountProxyInterface $accountProxy, string $code) {
    /** @var \Drupal\Core\Session\AccountInterface $account */
    $account = $accountProxy->getAccount();
    $isEligible = $this->isEligible($account, $code);
    if ($isEligible) {
      $user = User::load($account->id());
      $user->field_referent_code->setValue($code);
      $user->addRole('affiliate');
      $user->save();
      $this->setReferent($user);
      $dispatcher = \Drupal::service('event_dispatcher');
      return $this->statusMessage("registration_complete");
    }
    else {
      return $this->statusMessage("already_member");
    }
  }

  /**
   * Create a new user that and populate the referent code.
   *
   * @param string $referral_code
   *   The referral code we are assigning to the new user as referent code.
   *
   * @return \Drupalservice|void
   *   An instance of renderer used to display the register form on our page.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function submitAnonymous($referral_code) {
    $user = \Drupal::entityTypeManager()->getStorage('user')->create([]);
    $user->field_referent_code->setValue($referral_code);
    $user->addRole('affiliate');
    $this->setReferent($user);
    $formObject = \Drupal::entityTypeManager()
      ->getFormObject('user', 'register')
      ->setEntity($user);
    $form = \Drupal::formBuilder()->getForm($formObject);
    return \Drupal::service('renderer')->render($form);
  }

  /**
   * Checks if the current code is in use and therefore a valid one.
   *
   * @param string $code
   *   The affiliation code you want to check.
   *
   * @return bool
   *   A boolean that indicates if the given code is already assigned to a user
   *   and is therefore valid.
   */
  public function validCode(string $code) {
    /** @var array $affiliates */
    $affiliates = $this->affilatesLoadAll();
    foreach ($affiliates as $affiliate) {
      if ($code === $affiliate->field_referral_code->value) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
