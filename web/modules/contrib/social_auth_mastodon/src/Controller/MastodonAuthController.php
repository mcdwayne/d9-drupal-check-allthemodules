<?php

namespace Drupal\social_auth_mastodon\Controller;

use Drupal\Core\Render\RendererInterface;
use Drupal\social_auth\Controller\OAuth2ControllerBase;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\social_api\Plugin\NetworkManager;
use Drupal\social_auth\SocialAuthDataHandler;
use Drupal\social_auth\User\UserAuthenticator;
use Drupal\social_auth_mastodon\MastodonAuthManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Returns responses for Social Auth Mastodon module routes.
 */
class MastodonAuthController extends OAuth2ControllerBase {

  /**
   * MastodonAuthController constructor.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\social_api\Plugin\NetworkManager $network_manager
   *   Used to get an instance of social_auth_mastodon network plugin.
   * @param \Drupal\social_auth\User\UserAuthenticator $user_authenticator
   *   Manages Drupal authentication tasks for Social Auth.
   * @param \Drupal\social_auth_mastodon\MastodonAuthManager $mastodon_manager
   *   Used to manage authentication methods.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   Used to access GET parameters.
   * @param \Drupal\social_auth\SocialAuthDataHandler $data_handler
   *   The Social Auth data handler.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   Used to handle metadata for redirection to authentication URL.
   */
  public function __construct(MessengerInterface $messenger,
                              NetworkManager $network_manager,
                              UserAuthenticator $user_authenticator,
                              MastodonAuthManager $mastodon_manager,
                              RequestStack $request,
                              SocialAuthDataHandler $data_handler,
                              RendererInterface $renderer) {

    parent::__construct('Social Auth Mastodon', 'social_auth_mastodon',
                        $messenger, $network_manager, $user_authenticator,
                        $mastodon_manager, $request, $data_handler, $renderer);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger'),
      $container->get('plugin.network.manager'),
      $container->get('social_auth.user_authenticator'),
      $container->get('social_auth_mastodon.manager'),
      $container->get('request_stack'),
      $container->get('social_auth.data_handler'),
      $container->get('renderer')
    );
  }

  /**
   * Response for path 'user/login/mastodon/callback'.
   *
   * Mastodon returns the user here after user has authenticated in Mastodon.
   */
  public function callback() {
    // Checks if user cancel login via Mastodon.
    if ($this->request->getCurrentRequest()->query->has('error')) {
      $this->messenger->addError('You could not be authenticated.');

      return $this->redirect('user.login');
    }

    /* @var \Lrf141\OAuth2\Client\Provider\MastodonResourceOwner|null $profile */
    $profile = $this->processCallback();

    // If authentication was successful.
    if ($profile !== NULL) {

      // Gets (or not) extra initial data.
      $data = $this->userAuthenticator->checkProviderIsAssociated($profile->getId()) ? NULL : $this->providerManager->getExtraDetails();

      return $this->userAuthenticator->authenticateUser($profile->getName(), NULL, $profile->getId(), $this->providerManager->getAccessToken(), $profile->toArray()['avatar'], $data);
    }

    return $this->redirect('user.login');
  }

}
