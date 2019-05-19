<?php

namespace Drupal\social_auth_pbs\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\social_api\Plugin\NetworkManager;
use Drupal\social_auth\Controller\OAuth2ControllerBase;
use Drupal\social_auth\SocialAuthDataHandler;
use Drupal\social_auth\User\UserAuthenticator;
use Drupal\social_auth_pbs\PbsAuthManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Returns responses for Social Auth PBS module routes.
 */
class PbsAuthController extends OAuth2ControllerBase {

  /**
   * The JSON Serialize servicer.
   *
   * @var \Drupal\Component\Serialization\Json
   */
  protected $jsonSerializer;

  /**
   * PbsAuthController constructor.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Used for setting messages.
   * @param \Drupal\social_api\Plugin\NetworkManager $network_manager
   *   Used to get an instance of social_auth_pbs network plugin.
   * @param \Drupal\social_auth\User\UserAuthenticator $user_authenticator
   *   Manages user login/registration.
   * @param \Drupal\social_auth_pbs\PbsAuthManager $pbs_auth_manager
   *   Used to manage authentication methods.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   Used to access GET parameters.
   * @param \Drupal\social_auth\SocialAuthDataHandler $data_handler
   *   SocialAuthDataHandler object.
   * @param \Drupal\Component\Serialization\Json $json_serializer
   *   Used to serialize additional data.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   Used to handle metadata for redirection to authentication URL.
   */
  public function __construct(MessengerInterface $messenger,
                              NetworkManager $network_manager,
                              UserAuthenticator $user_authenticator,
                              PbsAuthManager $pbs_auth_manager,
                              RequestStack $request,
                              SocialAuthDataHandler $data_handler,
                              Json $json_serializer,
                              RendererInterface $renderer) {

    parent::__construct(
      'Social Auth PBS',
      'social_auth_pbs',
      $messenger,
      $network_manager,
      $user_authenticator,
      $pbs_auth_manager,
      $request,
      $data_handler,
      $renderer
    );
    $this->jsonSerializer = $json_serializer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger'),
      $container->get('plugin.network.manager'),
      $container->get('social_auth.user_authenticator'),
      $container->get('social_auth_pbs.manager'),
      $container->get('request_stack'),
      $container->get('social_auth.data_handler'),
      $container->get('serialization.json'),
      $container->get('renderer')
    );
  }

  /**
   * Response for path 'user/login/pbs/callback'.
   *
   * PBS returns the user here after user has authenticated.
   */
  public function callback() {

    /* @var \OpenPublicMedia\OAuth2\Client\Provider\PbsResourceOwner|null $profile */
    $profile = $this->processCallback();

    // If authentication was successful.
    if ($profile !== NULL) {

      // If user information could be retrieved.
      return $this->userAuthenticator->authenticateUser(
        $profile->getName(),
        $profile->getEmail(),
        $profile->getId(),
        $this->providerManager->getAccessToken(),
        $profile->getThumbnailUrl(),
        $this->jsonSerializer->encode($profile->toArray())
      );
    }

    return $this->redirect('user.login');
  }

}
