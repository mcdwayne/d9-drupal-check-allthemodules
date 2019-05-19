<?php

namespace Drupal\social_auth_itsme\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\social_api\Plugin\NetworkManager;
use Drupal\social_auth\SocialAuthDataHandler;
use Drupal\social_auth\SocialAuthUserManager;
use Drupal\social_auth_itsme\ItsmeAuthManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Returns responses for Social Auth itsme routes.
 */
class ItsmeAuthController extends ControllerBase {

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
   * The itsme authentication manager.
   *
   * @var \Drupal\social_auth_itsme\ItsmeAuthManager
   */
  private $itsmeManager;

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
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * ItsmeAuthController constructor.
   *
   * @param \Drupal\social_api\Plugin\NetworkManager $network_manager
   *   Used to get an instance of social_auth_itsme network plugin.
   * @param \Drupal\social_auth\SocialAuthUserManager $user_manager
   *   Manages user login/registration.
   * @param \Drupal\social_auth_itsme\ItsmeAuthManager $itsme_manager
   *   Used to manage authentication methods.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   Used to access GET parameters.
   * @param \Drupal\social_auth\SocialAuthDataHandler $data_handler
   *   SocialAuthDataHandler object.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   Used for logging errors.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(NetworkManager $network_manager,
                              SocialAuthUserManager $user_manager,
                              ItsmeAuthManager $itsme_manager,
                              RequestStack $request,
                              SocialAuthDataHandler $data_handler,
                              LoggerChannelFactoryInterface $logger_factory,
                              MessengerInterface $messenger) {

    $this->networkManager = $network_manager;
    $this->userManager = $user_manager;
    $this->itsmeManager = $itsme_manager;
    $this->request = $request;
    $this->dataHandler = $data_handler;
    $this->messenger = $messenger;

    // Sets the plugin id.
    $this->userManager->setPluginId('social_auth_itsme');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.network.manager'),
      $container->get('social_auth.user_manager'),
      $container->get('social_auth_itsme.manager'),
      $container->get('request_stack'),
      $container->get('social_auth.data_handler'),
      $container->get('logger.factory'),
      $container->get('messenger')
    );
  }

  /**
   * Response for path 'user/login/itsme'.
   *
   * Redirects the user to itsme for authentication.
   */
  public function redirectToProvider() {
    /** @var \Drupal\social_auth_itsme\Plugin\Network\ItsmeAuth $itsme_plugin */
    $itsme_plugin = $this->networkManager->createInstance('social_auth_itsme');
    /* @var \Nascom\ItsmeApiClient\Http\ApiClient\ApiClient|false $itsme */
    $itsme = $itsme_plugin->getSdk();

    // If itsme client could not be obtained.
    if (!$itsme) {
      $this->messenger->addError($this->t('Social Auth itsme not configured properly. Contact site administrator.'));
      return $this->redirect('user.login');
    }

    // If the itsme service was returned, inject it to $itsmeManager.
    $this->itsmeManager
      ->setClient($itsme)
      ->setSettings($itsme_plugin->getSettings());

    // Generates the URL where the user will be redirected for itsme login.
    if (!$itsme_login_url = $this->itsmeManager->getAuthorizationUrl()) {
      $this->messenger->addError($this->t('Social Auth itsme not configured properly. Contact site administrator.'));
      return $this->redirect('user.login');
    }

    return new TrustedRedirectResponse($itsme_login_url);
  }

  /**
   * Response for path 'user/login/itsme/callback'.
   *
   * The itsme API returns the user here after user has authenticated.
   */
  public function callback() {
    // Checks if user cancel login via itsme.
    $error = $this->request->getCurrentRequest()->get('error');
    if ($error == 'user_cancelled_login' || $error == 'user_cancelled_authorize') {
      $this->messenger->addError($this->t('You could not be authenticated.'));
      return $this->redirect('user.login');
    }

    /** @var \Drupal\social_auth_itsme\Plugin\Network\ItsmeAuth $itsme_plugin */
    $itsme_plugin = $this->networkManager->createInstance('social_auth_itsme');
    /* @var \Nascom\ItsmeApiClient\Http\ApiClient\ApiClient|false $itsme */
    $itsme = $itsme_plugin->getSdk();

    // If itsme client could not be obtained.
    if (!$itsme) {
      $this->messenger->addError($this->t('Social Auth itsme not configured properly. Contact site administrator.'));
      return $this->redirect('user.login');
    }

    // If the itsme service was returned, inject it to $itsmeManager.
    $this->itsmeManager
      ->setClient($itsme)
      ->setSettings($itsme_plugin->getSettings());

    // Gets user's info from itsme API.
    if (!$profile = $this->itsmeManager->getUserInfo()) {
      $this->messenger->addError($this->t('Itsme login failed, could not load itsme profile. Contact site administrator.'));
      return $this->redirect('user.login');
    }

    // If user information could be retrieved.
    return $this->userManager->authenticateUser($profile->getName()->getFullName(), $profile->getEmailAddress(), $profile->getUserId(), $this->itsmeManager->getAccessToken(), FALSE, json_encode($profile));
  }

}
