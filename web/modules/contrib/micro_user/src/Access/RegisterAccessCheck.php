<?php

namespace Drupal\micro_user\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\micro_site\SiteNegotiatorInterface;

/**
 * Access check for user registration routes.
 */
class RegisterAccessCheck implements AccessInterface {
  /**
   * The Site negotiator.
   *
   * @var \Drupal\micro_site\siteNegotiatorInterface
   */
  protected $negotiator;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs the object.
   *
   * @param SiteNegotiatorInterface $negotiator
   *   The domain negotiation service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(SiteNegotiatorInterface $negotiator, ConfigFactoryInterface $config_factory) {
    $this->negotiator = $negotiator;
    $this->configFactory = $config_factory;
  }
  /**
   * Checks access.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account) {
    $user_settings = \Drupal::config('user.settings');
    return AccessResult::allowedIf($account->isAnonymous() && $user_settings->get('register') != USER_REGISTER_ADMINISTRATORS_ONLY)->cacheUntilConfigurationChanges($user_settings);
  }

}
