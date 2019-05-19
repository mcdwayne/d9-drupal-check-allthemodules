<?php

namespace Drupal\social_auth_ok\Controller;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\social_api\Plugin\NetworkManager;
use Drupal\social_auth\Controller\OAuth2ControllerBase;
use Drupal\social_auth\SocialAuthDataHandler;
use Drupal\social_auth_ok\OkAuthManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\social_auth\User\UserAuthenticator;
use Symfony\Component\HttpFoundation\RequestStack;


/**
 * Class OkAuthController.
 */
class OkAuthController extends OAuth2ControllerBase {


  /**
   * Constructs a new OkAuthController object.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\social_api\Plugin\NetworkManager $network_manager
   *   Used to get an instance of social_auth_ok network plugin. 
   * @param \Drupal\social_auth\User\UserAuthenticator $user_authenticator
   *   Used to manage user authentication/registration.
   * @param \Drupal\social_auth_ok\OkAuthManager $ok_manager
   *   Used to manage authentication methods.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   Used to access GET parameters.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   * @param \Drupal\social_auth\SocialAuthDataHandler $data_handler
   *   The Social Auth data handler.
   */

  public function __construct(MessengerInterface $messenger,
                              NetworkManager $network_manager,
                              UserAuthenticator $user_authenticator,
                              OkAuthManager $ok_manager,
                              RequestStack $request,
                              SocialAuthDataHandler $data_handler) {

    parent::__construct('Social Auth OK', 'social_auth_ok', $messenger, $network_manager, $user_authenticator, $ok_manager, $request, $data_handler);
   }


  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger'),
      $container->get('plugin.network.manager'),
      $container->get('social_auth.user_authenticator'),
      $container->get('social_auth_ok.manager'),
      $container->get('request_stack'),
      $container->get('social_auth.data_handler')
    );
  }


  /** 
   * Response for path 'user/login/ok/callback'
   * OK returns the user here after user has authenticated. 
   */
  public function callback() {

    // Checks if authentication failed.
    if ($this->request->getCurrentRequest()->query->has('error')) {
      $this->messenger->addError($this->t('You could not be authenticated.'));
      return $this->redirect('user.login');
    }

    /* @var array|null $profile */
    $profile = $this->processCallback();

    // If authentication was successful.
    if ($profile !== NULL) { 
      $data = $profile->toArray(); 
      $email = isset($data['email']) ? $data['email'] : '';

      return $this->userAuthenticator->authenticateUser($profile->getName(), $email, $profile->getId(), $this->providerManager->getAccessToken(), $profile->getImageUrl());
    }

    return $this->redirect('user.login');  
  } 
}
