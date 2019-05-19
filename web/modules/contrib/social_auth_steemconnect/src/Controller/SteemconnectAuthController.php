<?php

namespace Drupal\social_auth_steemconnect\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\social_api\Plugin\NetworkManager;
use Drupal\social_auth\SocialAuthDataHandler;
use Drupal\social_auth\SocialAuthUserManager;
use Drupal\social_auth_steemconnect\SteemconnectAuthManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Returns responses for Simple Steemconnect Connect module routes.
 */
class SteemconnectAuthController extends ControllerBase {

  /**
   * The network plugin manager.
   *
   * @var \Drupal\social_api\Plugin\NetworkManager
   */
  private $networkManager;

  /**
   * The user manager.
   *
   * @var \Drupal\social_auth\SocialAuthUserManager
   */
  private $userManager;

  /**
   * The Steemconnect authentication manager.
   *
   * @var \Drupal\social_auth_steemconnect\SteemconnectAuthManager
   */
  private $steemconnectManager;

  /**
   * Used to access GET parameters.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  private $request;

  /**
   * The Social Auth Data Handler.
   *
   * @var \Drupal\social_auth\SocialAuthDataHandler
   */
  private $dataHandler;


  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * SteemconnectAuthController constructor.
   *
   * @param \Drupal\social_api\Plugin\NetworkManager $network_manager
   *   Used to get an instance of social_auth_steemconnect network plugin.
   * @param \Drupal\social_auth\SocialAuthUserManager $user_manager
   *   Manages user login/registration.
   * @param \Drupal\social_auth_steemconnect\SteemconnectAuthManager $steemconnect_manager
   *   Used to manage authentication methods.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   Used to access GET parameters.
   * @param \Drupal\social_auth\SocialAuthDataHandler $social_auth_data_handler
   *   SocialAuthDataHandler object.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   Used for logging errors.
   */
  public function __construct(NetworkManager $network_manager, SocialAuthUserManager $user_manager, SteemconnectAuthManager $steemconnect_manager, RequestStack $request, SocialAuthDataHandler $social_auth_data_handler, LoggerChannelFactoryInterface $logger_factory) {

    $this->networkManager = $network_manager;
    $this->userManager = $user_manager;
    $this->steemconnectManager = $steemconnect_manager;
    $this->request = $request;
    $this->dataHandler = $social_auth_data_handler;
    $this->loggerFactory = $logger_factory;

    // Sets the plugin id.
    $this->userManager->setPluginId('social_auth_steemconnect');

    // Sets the session keys to nullify if user could not logged in.
    // TODO add name, ...check in xDebug.
    $this->userManager->setSessionKeysToNullify(['access_token', 'oauth2state']);
    $this->setting = $this->config('social_auth_steemconnect.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.network.manager'),
      $container->get('social_auth.user_manager'),
      $container->get('social_auth_steemconnect.manager'),
      $container->get('request_stack'),
      $container->get('social_auth.data_handler'),
      $container->get('logger.factory')
    );
  }

  /**
   * Response for path 'user/login/steemconnect'.
   *
   * Redirects the user to Steemconnect for authentication.
   */
  public function redirectToSteemconnect() {
    /* @var \League\OAuth2\Client\Provider\Steemconnect false $steemconnect*/
    $steemconnect = $this->networkManager->createInstance('social_auth_steemconnect')->getSdk();

    // If Steemconnect client could not be obtained.
    if (!$steemconnect) {
      drupal_set_message($this->t('Social Auth Steemconnect not configured properly. Contact site administrator.'), 'error');
      return $this->redirect('user.login');
    }

    // Steemconnect service was returned, inject it to $steemconnectManager.
    $this->steemconnectManager->setClient($steemconnect);

    // Generates the URL where user will be redirected for Steemconnect login.
    // If the user did not have email permission granted on previous attempt,
    // we use the re-request URL requesting only the email address.
    $steemconnect_login_url = $this->steemconnectManager->getAuthorizationUrl();

    $state = $this->steemconnectManager->getState();

    $this->dataHandler->set('oauth2state', $state);

    return new TrustedRedirectResponse($steemconnect_login_url);
  }

  /**
   * Response for path 'user/login/steemconnect/callback'.
   *
   * Steemconnect returns the user here after authenticated in Steemconnect.
   */
  public function callback() {
    // Checks if user cancel login via Steemconnect.
    $error = $this->request->getCurrentRequest()->get('error');
    if ($error == 'access_denied') {
      drupal_set_message($this->t('You could not be authenticated.'), 'error');
      return $this->redirect('user.login');
    }

    /* @var \League\OAuth2\Client\Provider\Steemconnect false $steemconnect */
    $steemconnect = $this->networkManager->createInstance('social_auth_steemconnect')->getSdk();

    // If Steemconnect client could not be obtained.
    if (!$steemconnect) {
      drupal_set_message($this->t('Social Auth Steemconnect not configured properly. Contact site administrator.'), 'error');
      return $this->redirect('user.login');
    }

    $state = $this->dataHandler->get('oauth2state');

    // Retreives $_GET['state'].
    $retrievedState = $this->request->getCurrentRequest()->query->get('state');
    if (empty($retrievedState) || ($retrievedState !== $state)) {
      $this->userManager->nullifySessionKeys();
      drupal_set_message($this->t('Steemconnect login failed. Unvalid oAuth2 State.'), 'error');
      return $this->redirect('user.login');
    }

    // Saves access token to session.
    $this->dataHandler->set('access_token', $this->steemconnectManager->getAccessToken());

    $this->steemconnectManager->setClient($steemconnect)->authenticate();

    // Gets user's info from Steemconnect API.
    /* @var \League\OAuth2\Client\Provider\SteemconnectResourceOwner $profile */
    if (!$profile = $this->steemconnectManager->getUserInfo()) {
      drupal_set_message($this->t('Steemconnect login failed, could not load Steemconnect profile. Contact site administrator.'), 'error');
      return $this->redirect('user.login');
    }

    // Gets (or not) extra initial data.
    $data = $this->userManager->checkIfUserExists($profile->getId()) ? NULL : $this->steemconnectManager->getExtraDetails();

    // If user information could be retrieved.
    // Note: getEmail() in guix77/oauth2-steemconnect sends a fake email like:
    // 123456@@fake-steemconnect-email.com (account ID@...)
    // Indeed, Steemconnect does not provide emails.
    return $this->userManager->authenticateUser($profile->getName(), $profile->getEmail(), $profile->getId(), $this->steemconnectManager->getAccessToken(), $profile->getProfileImage(), $data);
  }

}
