<?php

namespace Drupal\user_hash\Authentication\Provider;

use Drupal\Core\Authentication\AuthenticationProviderInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Flood\FloodInterface;
use Drupal\user\UserAuthInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * User Hash authentication provider.
 */
class UserHash implements AuthenticationProviderInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The user auth service.
   *
   * @var \Drupal\user\UserAuthInterface
   */
  protected $userAuth;

  /**
   * The flood service.
   *
   * @var \Drupal\Core\Flood\FloodInterface
   */
  protected $flood;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a User Hash authentication provider object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\user\UserAuthInterface $user_auth
   *   The user authentication service.
   * @param \Drupal\Core\Flood\FloodInterface $flood
   *   The flood service.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, UserAuthInterface $user_auth, FloodInterface $flood, EntityManagerInterface $entity_manager) {
    $this->configFactory = $config_factory;
    $this->userAuth = $user_auth;
    $this->flood = $flood;
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(Request $request) {
    $username = $request->headers->get('X_USER_NAME');
    $hash = $request->headers->get('X_USER_HASH');
    return isset($username) && isset($hash);
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate(Request $request) {
    $flood_config = $this->configFactory->get('user.flood');
    $username = $request->headers->get('X_USER_NAME');
    $hash = $request->headers->get('X_USER_HASH');
    if ($this->flood->isAllowed('user_hash.failed_login_ip', $flood_config->get('ip_limit'), $flood_config->get('ip_window'))) {
      /** @var \Drupal\user_hash\Services $service */
      $service = \Drupal::service('user_hash.services');
      $account = $service->validateHash($username, $hash);
      if ($account) {
        if ($flood_config->get('uid_only')) {
          $identifier = $account->id();
        }
        else {
          $identifier = $account->id() . '-' . $request->getClientIP();
        }
        if ($this->flood->isAllowed('user_hash.failed_login_user', $flood_config->get('user_limit'), $flood_config->get('user_window'), $identifier)) {
          if ($account->userHashIsValid) {
            $this->flood->clear('user_hash.failed_login_user', $identifier);
            return $account;
          }
          else {
            $this->flood->register('user_hash.failed_login_user', $flood_config->get('user_window'), $identifier);
          }
        }
      }
    }
    $this->flood->register('user_hash.failed_login_ip', $flood_config->get('ip_window'));
    return [];
  }

}
