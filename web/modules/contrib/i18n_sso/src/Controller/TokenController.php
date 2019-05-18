<?php

namespace Drupal\i18n_sso\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\i18n_sso\Service\Token;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class TokenController.
 *
 * @package Drupal\i18n_sso\Controller
 */
class TokenController extends ControllerBase {

  /**
   * The token service.
   *
   * @var \Drupal\i18n_sso\Service\Token
   */
  protected $token;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * TokenController constructor.
   *
   * @param \Drupal\i18n_sso\Service\Token $token
   *   Token service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack to get the current request object.
   */
  public function __construct(Token $token, RequestStack $requestStack) {
    $this->token = $token;
    $this->request = $requestStack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('i18n_sso.token'),
      $container->get('request_stack')
    );
  }

  /**
   * Returns json_encoded object containing token.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response.
   */
  public function getToken() {
    $token = new \stdClass();
    $token->token = FALSE;

    if ($this->currentUser()->isAuthenticated()) {
      $token = $this->token->getToken(
        $this->request->getClientIp(),
        $this->currentUser()->id()
      );
      if (empty($token)) {
        $token = $this->token->createToken(
          $this->request->getClientIp(),
          $this->currentUser()->id()
        );
      }
      $token->message = $this->t('Your account has been found. Please wait while we log you in.');
    }
    else {
      $token = new \stdClass();
      $token->token = FALSE;
      $token->message = $this->t('You are not logged in on main website. Please go to the main website, log-in and try again.');
    }
    $response = JsonResponse::create($token);
    $response->setPrivate()->setMaxAge(0);
    return $response;
  }

  /**
   * Logs user in if token is valid for user IP and deletes it.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response.
   */
  public function useToken() {
    $response = JsonResponse::create([
      'success' => FALSE,
      'message' => 'An error occurred while trying to log you in. Please try again later.',
    ]);

    $uid = $this->token->getUserId(
      $this->request->getClientIp(),
      $this->request->get('token', '')
    );
    if (!empty($uid)) {
      $user = $this->entityTypeManager()->getStorage('user')->load($uid);
      user_login_finalize($user);
      $data['success'] = TRUE;
      $data['message'] = $this->t('You have been successfully logged in. Please wait for the page to refresh.');
      $response->setData($data);
      $this->token->deleteToken(
        $this->request->getClientIp(),
        $this->request->get('token', '')
      );
    }
    return $response->setPrivate()->setMaxAge(0);
  }

}
