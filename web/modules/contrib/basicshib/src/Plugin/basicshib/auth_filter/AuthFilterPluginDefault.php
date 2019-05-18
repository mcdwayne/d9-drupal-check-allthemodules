<?php
/**
 * Created by PhpStorm.
 * User: th140
 * Date: 11/17/17
 * Time: 9:02 AM
 */

namespace Drupal\basicshib\Plugin\basicshib\auth_filter;


use Drupal\basicshib\Annotation\BasicShibAuthFilter;
use Drupal\basicshib\AuthenticationHandler;
use Drupal\basicshib\Plugin\AuthenticationFilterResult;
use Drupal\basicshib\Plugin\AuthFilterPluginInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AuthenticationFilterPluginDefault
 *
 * @package Drupal\basicshib\Plugin\basicshib\auth_filter
 *
 * @BasicShibAuthFilter(
 *   id = "basicshib",
 *   title = "Basic authentication filter"
 * )
 */
class AuthFilterPluginDefault implements AuthFilterPluginInterface, ContainerFactoryPluginInterface {
  /**
   * @var ImmutableConfig
   */
  private $configuration;

  /**
   * AuthenticationFilterPluginDefault constructor.
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


    return new static(
      $container->get('config.factory')->get('basicshib.auth_filter')
    );
  }


  /**
   * @inheritDoc
   */
  public function isUserCreationAllowed() {
    $create_filter = $this->configuration
      ->get('create');
    return $create_filter['allow'];
  }

  /**
   * @inheritDoc
   */
  public function isExistingUserLoginAllowed(UserInterface $account) {
    return true;
  }

  /**
   * @inheritDoc
   */
  public function getError($code, UserInterface $account = null) {
    switch ($code) {
      case self::ERROR_CREATION_NOT_ALLOWED:
        $create_filter = $this->configuration
          ->get('create');
        return $create_filter['error'];
    }
  }

  /**
   * @inheritDoc
   */
  public function checkSession(Request $request, AccountProxyInterface $account) {
    return AuthenticationHandler::AUTHCHECK_IGNORE;
  }


}
