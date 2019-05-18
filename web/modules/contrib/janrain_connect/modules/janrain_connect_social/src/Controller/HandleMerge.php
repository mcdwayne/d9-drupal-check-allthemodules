<?php

namespace Drupal\janrain_connect_social\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Drupal\janrain_connect\Service\JanrainConnectConnector;
use Drupal\janrain_connect\Service\JanrainConnectLogin;

/**
 * Creates a tiles dashboard.
 */
class HandleMerge extends ControllerBase {

  /**
   * Request object.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  private $requestStack;

  /**
   * Symfony session handler.
   *
   * @var \Symfony\Component\HttpFoundation\Session\Session
   */
  private $session;

  /**
   * JanrainConnectConnector.
   *
   * @var \Drupal\janrain_connect\Service\JanrainConnectConnector
   */
  private $janrainConnector;

  /**
   * JanrainConnectLogin.
   *
   * @var \Drupal\janrain_connect\Service\JanrainConnectLogin
   */
  private $janrainLogin;

  /**
   * {@inheritdoc}
   */
  public function __construct(RequestStack $request_stack, Session $session, JanrainConnectConnector $janrain_connector, JanrainConnectLogin $janrain_login) {
    $this->requestStack = $request_stack;
    $this->session = $session;
    $this->janrainConnector = $janrain_connector;
    $this->janrainLogin = $janrain_login;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('session'),
      $container->get('janrain_connect.connector'),
      $container->get('janrain_connect.login')
    );
  }

  /**
   * Renders controller.
   */
  public function process() {
    // Store engage token received from Janrain. This is the new account.
    $engage_token = $this->requestStack->getCurrentRequest()->request->get('token');
    // This is the account Janrain already has recorded.
    $engage_merge_token = $this->session->get('janrain_connect_social_engage_token');

    if (!$engage_token || !$engage_merge_token) {
      // Something is wrong. We should have received the token from Janrain.
      // @todo Improve journey
      return $this->redirect('<front>');
    }

    $merge_result = $this->janrainConnector->socialLogin(
      $engage_token,
      $engage_merge_token,
      $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost()
    );

    $logged = $this->janrainLogin->mergeLogin($merge_result['access_token'], $merge_result['capture_user']->email);
    if ($logged) {
      // TODO: Improve user journey.
      return $this->redirect('<front>');
    }

    // TODO: Improve user journey.
    return $this->redirect('<front>');
  }

}
