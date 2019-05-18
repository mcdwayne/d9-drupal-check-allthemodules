<?php

namespace Drupal\shib_auth\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\shib_auth\Login\LoginHandler;

/**
 * Class LogoutController.
 *
 * @package Drupal\shib_auth\Controller
 */
class LoginController extends ControllerBase {

  /**
   * The login handler.
   *
   * @var \Drupal\shib_auth\Login\LoginHandler
   */
  private $loginHandler;

  /**
   * The redirect destination.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $redirectDestination;

  /**
   * LoginController constructor.
   *
   * @param \Drupal\shib_auth\Login\LoginHandler $login_handler
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   */
  public function __construct(LoginHandler $login_handler) {
    $this->loginHandler = $login_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('shib_auth.login_handler')
      );
  }

  /**
   * Login-- Processes Drupal login, then redirects.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function login() {

    if (!empty($this->loginHandler->getShibSession()->getSessionId())) {
      // Check if there is an active drupal login.
      if (\Drupal::currentUser()->isAnonymous()) {
        // Call the shib login function in the login handler class.
        if ($response = $this->loginHandler->shibLogin()) {
          // We need to remove the destination or it will redirect to that
          // rather than where we actually want to go.
          \Drupal::request()->query->remove('destination');
          return $response;
        }
      }
    }

    // Will redirect to ?destination by default.
    return $this->redirect('<front>');

  }

}
