<?php

namespace Drupal\email_confirmer_user\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserDataInterface;
use Drupal\user\UserInterface;

/**
 * Access check for user email change operations.
 */
class UserEmailPendingChangeAccess implements AccessInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The user data service.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * Constructs a UserEmailChangeAccess instance.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\user\UserDataInterface $user_data
   *   The user data service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, UserDataInterface $user_data) {
    $this->configFactory = $config_factory;
    $this->userData = $user_data;
  }

  /**
   * Checks access to the given user's pending email change.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user whose email address is pending confirmation of change.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(UserInterface $user, AccountInterface $account) {
    $target_account = $user;

    // Anonymous users cannot have any pending email change.
    if ($target_account->isAnonymous()) {
      return AccessResult::forbidden();
    }

    // Email change confirmation has to be enabled.
    $module_config = $this->configFactory->get('email_confirmer_user.settings');
    $change_config = $module_config->get('user_email_change');
    if (empty($change_config['enabled'])) {
      return AccessResult::forbidden()->addCacheableDependency($module_config);
    }

    // The target user must have a pending email change.
    if (!$new_email = $this->userData->get('email_confirmer_user', $user->id(), 'email_change_new_address')) {
      $access_result = AccessResult::forbidden();
    }
    else {
      // Access to update the target account is required by the current user.
      $access_result = $target_account->access('update', $account, TRUE);
    }

    return $access_result->cachePerUser()->addCacheableDependency($target_account);
  }

}
