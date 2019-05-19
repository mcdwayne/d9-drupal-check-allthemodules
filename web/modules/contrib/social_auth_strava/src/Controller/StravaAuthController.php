<?php

namespace Drupal\social_auth_strava\Controller;

use Drupal\Core\Controller\ControllerBase;
use Strava\API\Oauth;
use Drupal\social_api\Plugin\NetworkManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Drupal\social_auth\SocialAuthUserManager;
use Drupal\social_auth_strava\StravaAuthManager;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Render\RenderContext;

/**
 * Manages requests to the Strava API.
 */
class StravaAuthController extends ControllerBase {


  /**
   * The network plugin manager.
   *
   * @var \Drupal\social_api\Plugin\NetworkManager
   */
  protected $networkManager;

  /**
   * The Strava authentication manager.
   *
   * @var \Drupal\social_auth_strava\StravaAuthManager
   */
  protected $stravaManager;

  /**
   * The user manager.
   *
   * @var \Drupal\social_auth\SocialAuthUserManager
   */
  protected $userManager;

  /**
   * The session manager.
   *
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
   */
  protected $session;

  /**
   * GoogleLoginController constructor.
   *
   * @param \Drupal\social_api\Plugin\NetworkManager $network_manager
   *   Used to get an instance of social_auth_strava network plugin.
   * @param \Drupal\social_auth\SocialAuthUserManager $userManager
   *   Manages user login/registration.
   * @param \Drupal\social_auth_strava\StravaAuthManager $stravaManager
   *   Used to manage authentication methods.
   * @param SessionInterface $session
   *   Used to store the access token into a session variable.
   */
  public function __construct(NetworkManager $network_manager, SocialAuthUserManager $userManager, StravaAuthManager $stravaManager, SessionInterface $session) {
    $this->networkManager = $network_manager;
    $this->userManager = $userManager;
    $this->stravaManager = $stravaManager;
    $this->session = $session;
    // Sets the plugin id.
    $this->userManager->setPluginId('social_auth_strava');
    // Sets the session keys to nullify if user could not logged in.
    $this->userManager->setSessionKeysToNullify(['social_auth_strava_access_token']);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.network.manager'),
      $container->get('social_auth.user_manager'),
      $container->get('social_auth_strava.manager'),
      $container->get('session')
    );
  }

  /**
   * Redirect to Strava Services Authentication page.
   */
  public function redirectToStrava() {
    // We need to avoid rendering too early.
    $context = new RenderContext();
    /* @var OAuth $client */
    $client = \Drupal::service('renderer')->executeInRenderContext($context, function () {
      return $this->networkManager->createInstance('social_auth_strava')->getSdk();
    });

    // If Strava client could not be obtained.
    if (!$client) {
      drupal_set_message($this->t('Social Auth Strava not configured properly. Contact site administrator.'), 'error');
      return $this->redirect('user.login');
    }
    // Strava service was returned, inject it to $linkedinManager.
    $this->stravaManager->setClient($client);

    return new TrustedRedirectResponse($client->getAuthorizationUrl(['scope' => ['public']]));
  }

  /**
   * Callback function to login user.
   */
  public function callback() {
    $client = $this->networkManager->createInstance('social_auth_strava')->getSdk();

    $this->stravaManager->setClient($client)->authenticate();
    // Saves access token so that event subscribers can call Strava API.
    $this->session->set('social_auth_strava_access_token', $this->stravaManager->getAccessToken());

    // Gets user information.
    $user = $this->stravaManager->getUserInfo();

    // If user information could be retrieved.
    if ($user) {
      $picture = (isset($user->imageUrl)) ? $user->imageUrl : FALSE;

      $fullname = $user->firstName . ' ' . $user->lastName;
      return $this->userManager->authenticateUser($user->email, $fullname,
        $user->uid, $picture);
    }

    drupal_set_message($this->t('You could not be authenticated, please contact the administrator'), 'error');
    return $this->redirect('user.login');
  }
}
