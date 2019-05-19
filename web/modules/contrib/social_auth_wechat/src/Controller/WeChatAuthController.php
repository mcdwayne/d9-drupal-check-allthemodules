<?php

namespace Drupal\social_auth_wechat\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Drupal\social_api\Plugin\NetworkManager;
use Drupal\social_auth\SocialAuthUserManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Zend\Diactoros\Response\RedirectResponse;

/**
 * Manages requests to WeChat API.
 *
 * Most of the code here is specific to implement a WeChat login process. Social
 * Networking services might require different approaches.
 */
class WeChatAuthController extends ControllerBase {

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
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * WeChatLoginController constructor.
   *
   * @param \Drupal\social_api\Plugin\NetworkManager $network_manager
   *   Used to get an instance of social_auth_google network plugin.
   * @param \Drupal\social_auth\SocialAuthUserManager $user_manager
   *   Manages user login/registration.
   */
  public function __construct(NetworkManager $network_manager,
                              SocialAuthUserManager $user_manager,
                              MessengerInterface $messenger) {
    $this->networkManager = $network_manager;
    $this->userManager = $user_manager;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
                $container->get('plugin.network.manager'),
                $container->get('social_auth.user_manager'),
                $container->get('messenger')
                );
  }

  /**
   * Redirects to WeChat Services Authentication page.
   *
   * Most of the Social Networks' API require you to redirect users to a
   * authentication page. This method is not a mandatory one, instead you must
   * adapt to the requirements of the module you are implementing.
   *
   * This method is called in 'social_auth_wechat.redirect_to_wechat' route.
   *
   * @see social_auth_wechat.routing.yml
   *
   * This method is triggered when the user loads user/login/wechat. It creates
   * an instance of the Network Plugin 'social auth wechat' and returns an
   * instance of the \Overtrue\Socialite\Providers\WeChatProvider object.
   *
   * It later sets the permissions that should be asked for, and redirects the
   * user to WeChat Accounts to allow him to grant those permissions.
   *
   * After the user grants permission, WeChat redirects him to a url specified
   * in the WeChat project settings. In this case, it should redirects to
   * 'user/login/wechat/callback', which calls the callback method.
   *
   * @return \Zend\Diactoros\Response\RedirectResponse
   *   Redirection to WeChat Accounts.
   */
  public function redirectToWeChat() {
    /** @var \Drupal\Core\Session\AccountInterface $current_user */
    $current_user = $this->currentUser();
    if ($current_user->isAnonymous()) {
      // Creates an instance of the Network Plugin and gets the SDK.
      /** @var \Overtrue\Socialite\Providers\WeChatProvider $client */
      $client = $this->networkManager->createInstance('social_auth_wechat')->getSdk();
      // Redirects to WeChat Accounts to allow the user grant the permissions.
      return new RedirectResponse($client->redirect()->getTargetUrl());
    }
    else {
      $post_login = \Drupal::configFactory()->get('social_auth.settings')->get('post_login');

      return new \Symfony\Component\HttpFoundation\RedirectResponse(Url::fromUserInput($post_login)->toString());
    }
  }

  /**
   * Callback function to login user.
   *
   * Most of the Social Networks' API redirects to callback url. This method is
   * not a mandatory one, instead you must adapt to the requirements of the
   * module you are implementing.
   *
   * This method is called in 'social_auth_wechat.callback' route.
   *
   * @see social_auth_wechat.routing.yml
   *
   * This method is triggered when the path user/login/wechat/callback is
   * loaded. It creates an instance of the Network Plugin 'social auth wechat'.
   *
   * It later authenticates the user and creates the service to obtain data
   * about the user.
   *
   * After the user is authenticated, it checks if a user with the same email
   * has already registered. If so, it logins that user; if not, it creates
   * a new user with the information provided by the social network and logins
   * the new user.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function callback() {
    // Creates the Network Plugin instance and get the SDK.
    /* @var \Overtrue\Socialite\Providers\WeChatProvider $client */
    $client = $this->networkManager->createInstance('social_auth_wechat')->getSdk();

    // Gets user information.
    /** @var \Overtrue\Socialite\User $user */
    $user = $client->user();

    // If user information could be retrieved.
    if ($user) {
      $email = $user->getEmail() ? $user->getEmail() : $user->getId() . '@wechat';
      $username = $user->getNickname() ? $user->getNickname() : 'wechat' . $user->getId();
      $token = $user->getToken();
      // Uses authenticateUser method to create and/or login an user.
      return $this->userManager->authenticateUser($username, $email, $user->getId(), $token, $user->getAvatar(), ' ');
    }

    $this->messenger->addError($this->t('You could not be authenticated, please contact the administrator'));
    return $this->redirect('user.login');
  }

}
