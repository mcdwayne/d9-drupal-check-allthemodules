<?php

namespace Drupal\basicshib_test\Plugin\basicshib\auth_filter;

use Drupal\basicshib\Plugin\AuthFilterPluginInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @BasicShibAuthFilter(
 *   id = "basicshib_test",
 *   title = "Test auth filter"
 * )
 */
class BasicShibTestAuthFilter implements AuthFilterPluginInterface, ContainerFactoryPluginInterface {

  /**
   * @var ImmutableConfig
   */
  private $configuration;

  /**
   * BasicShibTestAuthFilter constructor.
   *
   * @param ImmutableConfig $configuration
   */
  public function __construct(ImmutableConfig $configuration) {
    $this->configuration = $configuration;
  }

  /**
   * @inheritDoc
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $configuration = $container
      ->get('config.factory')
      ->get('basicshib_test.settings');
    return new static($configuration);
  }

  /**
   * @inheritDoc
   */
  public function isUserCreationAllowed() {
    $auth_filter = $this->configuration->get('auth_filter');
    return $auth_filter['user_creation_allowed'];
  }

  /**
   * @inheritDoc
   */
  public function getError($code, UserInterface $account = NULL) {
    $auth_filter = $this->configuration->get('auth_filter');
    return $auth_filter['error'];
  }

  /**
   * @inheritDoc
   */
  public function isExistingUserLoginAllowed(UserInterface $account) {
    $auth_filter = $this->configuration->get('auth_filter');
    return $auth_filter['existing_user_login_allowed'];
  }

  /**
   * @inheritDoc
   */
  public function checkSession(Request $request, AccountProxyInterface $account) {
    $auth_filter = $this->configuration->get('auth_filter');
    return $auth_filter['check_session_return_value'];
  }
}
