<?php

namespace Drupal\coupon_for_role;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\coupon_for_role\Exception\CouponAlreadyUsedException;

/**
 * CouponForRoleCouponManager service.
 */
class CouponForRoleCouponManager {

  const TABLE_NAME = 'coupon_for_role_coupons';

  /**
   * Database.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a CouponForRoleCouponManager object.
   */
  public function __construct(Connection $database, EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler) {
    $this->database = $database;
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * Generate a coupon.
   */
  public function generateCoupon($role, $expiry, $type = CouponConstants::ABSOLUTE_DATE_TYPE) {
    $role = $this->entityTypeManager->getStorage('user_role')
      ->load($role);
    if (!$role) {
      throw new \InvalidArgumentException('No role found with role name ' . $role);
    }
    // Try to get a code that is not taken.
    $code = $this->generateValidNewCode();
    // Insert it into the database.
    $data = [
      'status' => CouponConstants::STATUS_ACTIVE,
      'role' => $role->id(),
      'coupon' => $code,
      'expires' => $expiry,
      'type' => $type,
    ];
    if ($type == CouponConstants::RELATIVE_DATE_TYPE) {
      // Treat the expiry part as something we can pass to strtotime, and set an
      // expiry in a long time.
      $data['expires'] = strtotime('+10years');
      $data['data'] = [
        'expires' => $expiry,
      ];
    }
    $result = $this->saveCode($data);
    if (!$result) {
      throw new \Exception('The code data was not saved');
    }
    return $code;
  }

  /**
   * Revoke roles if necessary.
   */
  public function handleExpiredCoupon($coupon) {
    $coupon = (array) $coupon;
    // @todo: Use constants for this.
    $coupon['status'] = 2;
    $this->saveCode($coupon);
    if (!$coupon['uid']) {
      // Well, no one claimed it.
      return;
    }
    // See if we can load the user.
    if (!$account = $this->entityTypeManager->getStorage('user')->load($coupon['uid'])) {
      return;
    }
    // Find all the roles.
    $roles = $account->get('roles')->getValue();
    foreach ($roles as $delta => $role) {
      if ($role['target_id'] == $coupon['role']) {
        unset($roles[$delta]);
      }
    }
    $account->set('roles', $roles);
    $account->save();
    $this->moduleHandler->invokeAll('coupon_for_role_role_revoked', $coupon, $account);

  }

  /**
   * Redeem a coupon.
   */
  public function redeemCoupon($code, AccountInterface $account) {
    if (!$code_data = $this->getCodeDataBycode($code)) {
      throw new \Exception('The code ' . $code . ' was not found');
    }
    // If the code is already used, inform about this.
    if ($code_data['status'] == CouponConstants::STATUS_INACTIVE) {
      throw new CouponAlreadyUsedException('The coupon code ' . $code . ' has already been used.');
    }
    $code_data['status'] = CouponConstants::STATUS_INACTIVE;
    $code_data['uid'] = $account->id();
    if ($code_data['type'] == CouponConstants::RELATIVE_DATE_TYPE) {
      // Change the expire, based on the current date.
      $code_data["expires"] = strtotime($code_data["data"]["expires"]);
    }
    $this->saveCode($code_data);
    // Load the actual user entity.
    $user = $this->entityTypeManager->getStorage('user')
      ->load($account->id());
    // Grant the role to the user.
    // @todo: What if they already have the role?
    $roles = $user->get('roles')->getValue();
    $roles[] = $code_data['role'];
    $user->set('roles', $roles);
    $user->save();
  }

  /**
   * Saves code.
   */
  public function saveCode($code_data) {
    if (empty($code_data['data'])) {
      $code_data['data'] = [];
    }
    $code_data['data'] = serialize($code_data['data']);
    return $this->database->merge(self::TABLE_NAME)
      ->fields($code_data)
      ->condition('coupon', $code_data['coupon'])
      ->execute();
  }

  /**
   * Generates a new valid code that does not already exist.
   */
  protected function generateValidNewCode() {
    $has_code = FALSE;
    // @todo: Make this configurable.
    $number_of_characters = 6;
    $tries = 0;
    $max_tries = 100;
    while (!$has_code) {
      $code_suggestion = $this->generateCode($number_of_characters);
      // See if we already have it.
      if (!$existing_code = $this->getCodeDataBycode($code_suggestion)) {
        break;
      }
      $tries++;
      // @todo: MAke configurable?
      if ($tries > $max_tries) {
        throw new \Exception('Could not generate a new code in ' . $max_tries . ' tries');
      }
    }
    return $code_suggestion;
  }

  /**
   * Get code data.
   */
  public function getCodeDataBycode($code) {
    $data = $this->database
      ->select(self::TABLE_NAME, 'c')
      ->fields('c')
      ->condition('c.coupon', $code)
      ->execute()
      ->fetchAssoc();
    if (!$data) {
      return FALSE;
    }
    $data['data'] = @unserialize($data['data']);
    return $data;
  }

  /**
   * Generate a random code based on number of characters.
   */
  protected function generateCode($number_of_characters) {
    // @todo: Make configurable?
    $characters = [
      'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'J', 'K', 'L', 'M', 'N', 'P', 'R',
      'T', 'W', 'X', 'Y', 'Z', '1', '2', '3', '4', '6', '7', '8', '9',
    ];
    $code = '';
    while (strlen($code) < $number_of_characters) {
      $code .= $characters[rand(0, count($characters) - 1)];
    }
    return $code;
  }

}
