<?php

namespace Drupal\social_post_facebook\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\social_api\Plugin\NetworkManager;
use Drupal\social_post\SocialPostDataHandler;

use Drupal\social_post\SocialPostManager;
use Drupal\social_post_facebook\FacebookPostAuthManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Returns responses for Simple FB Connect module routes.
 */
class FacebookPostController extends ControllerBase {

  /**
   * The network plugin manager.
   *
   * @var \Drupal\social_api\Plugin\NetworkManager
   */
  private $networkManager;

  /**
   * The Facebook authentication manager.
   *
   * @var \Drupal\social_auth_facebook\FacebookAuthManager
   */
  private $facebookManager;

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
   * The data point to be collected.
   *
   * @var string
   */
  private $dataPoints;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The social post manager.
   *
   * @var \Drupal\social_post\SocialPostManager
   */
  protected $postManager;

  /**
   * FacebookAuthController constructor.
   *
   * @param \Drupal\social_api\Plugin\NetworkManager $network_manager
   *   Used to get an instance of social_auth_facebook network plugin.
   * @param \Drupal\social_post\SocialPostManager $user_manager
   *   Manages user login/registration.
   * @param \Drupal\social_post_facebook\FacebookPostAuthManager $facebook_manager
   *   Used to manage authentication methods.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   Used to access GET parameters.
   * @param \Drupal\social_post\SocialPostDataHandler $social_auth_data_handler
   *   SocialAuthDataHandler object.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   Used for logging errors.
   */
  public function __construct(NetworkManager $network_manager, SocialPostManager $post_manager, FacebookPostAuthManager $facebook_manager, RequestStack $request, SocialPostDataHandler $social_auth_data_handler, LoggerChannelFactoryInterface $logger_factory) {

    $this->networkManager = $network_manager;
    $this->postManager = $post_manager;
    $this->facebookManager = $facebook_manager;
    $this->request = $request;
    $this->dataHandler = $social_auth_data_handler;
    $this->loggerFactory = $logger_factory;

    $this->postManager->setPluginId('social_post_facebook');

    // Sets session prefix for data handler.
    $this->dataHandler->getSessionPrefix('social_post_facebook');

    // Sets the plugin id.
    // Sets the session keys to nullify if user could not logged in.
    // $this->facebookManager->setSessionKeysToNullify(['access_token']);.
    $this->setting = $this->config('social_post_facebook.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.network.manager'),
      $container->get('social_post.post_manager'),
      $container->get('facebook_post.social_post_auth_manager'),
      $container->get('request_stack'),
      $container->get('social_post.data_handler'),
      $container->get('logger.factory')
    );
  }

  /**
   * Redirects the user to FB for authentication.
   */
  public function redirectToFb() {
    /* @var \League\OAuth2\Client\Provider\Facebook false $facebook */
    $facebook = $this->networkManager->createInstance('social_post_facebook')->getSdk();

    // If facebook client could not be obtained.
    if (!$facebook) {
      drupal_set_message($this->t('Social Auth Facebook not configured properly. Contact site administrator.'), 'error');
      return $this->redirect('user.login');
    }

    // Google service was returned, inject it to $googleManager.
    $this->facebookManager->setClient($facebook);

    // Generates the URL where the user will be redirected for FB login.
    // If the user did not have email permission granted on previous attempt,
    // we use the re-request URL requesting only the email address.
    $fb_login_url = $this->facebookManager->getFbLoginUrl();

    $state = $this->facebookManager->getState();

    $this->dataHandler->set('oAuth2State', $state);

    return new TrustedRedirectResponse($fb_login_url);
  }

  /**
   * Response for path 'user/login/facebook/callback'.
   *
   * Facebook returns the user here after user has authenticated in FB.
   */
  public function callback() {
    // Checks if user cancel login via Facebook.
    $error = $this->request->getCurrentRequest()->get('error');
    if ($error == 'access_denied') {
      drupal_set_message($this->t('You could not be authenticated.'), 'error');
      return $this->redirect('user.login');
    }

    /* @var \League\OAuth2\Client\Provider\Facebook false $facebook */
    $facebook = $this->networkManager->createInstance('social_post_facebook')->getSdk();

    // If facebook client could not be obtained.
    if (!$facebook) {
      drupal_set_message($this->t('Social Auth Facebook not configured properly. Contact site administrator.'), 'error');
      return $this->redirect('user.login');
    }

    $state = $this->dataHandler->get('oAuth2State');

    // Retrieves $_GET['state'].
    $retrievedState = $this->request->getCurrentRequest()->query->get('state');
    if (empty($retrievedState) || ($retrievedState !== $state)) {
      $this->userManager->nullifySessionKeys();
      drupal_set_message($this->t('Google login failed. Unvalid oAuth2 State.'), 'error');
      return $this->redirect('user.login');
    }

    $this->facebookManager->setClient($facebook)->authenticate();

    // Gets user's email from the FB profile.
    if (!$email = $this->facebookManager->getUserInfo()->getEmail()) {
      drupal_set_message($this->t('Facebook login failed. This site requires permission to get your email address.'), 'error');
      return $this->redirect('user.login');
    }
    if (!$this->postManager->checkIfUserExists($this->facebookManager->getUserInfo()->getId())) {
      $this->postManager->addRecord('social_post_facebook', $this->facebookManager->getUserInfo()->getId(), $this->facebookManager->getAccessToken(), $this->facebookManager->getUserInfo()->getName(), '');
    }
    return $this->redirect('entity.user.edit_form', ['user' => $this->postManager->getCurrentUser()]);
  }

}
