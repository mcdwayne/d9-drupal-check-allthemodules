<?php

namespace Drupal\social_post_weibo\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\social_api\Plugin\NetworkManager;
use Drupal\social_post\SocialPostDataHandler;
use Drupal\social_post\SocialPostManager;
use Drupal\social_post_weibo\WeiboPostAuthManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Zend\Diactoros\Response\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Manages requests to Weibo.
 */
class WeiboPostController extends ControllerBase {

  /**
   * The network plugin manager.
   *
   * @var \Drupal\social_api\Plugin\NetworkManager
   */
  private $networkManager;

  /**
   * The LinkedIn authentication manager.
   *
   * @var \Drupal\social_post_weibo\WeiboPostAuthManager
   */
  private $providerManager;

  /**
   * The Social Auth Data Handler.
   *
   * @var \Drupal\social_post\SocialPostDataHandler
   */
  private $dataHandler;

  /**
   * Used to access GET parameters.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  private $request;

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
   * WeiboAuthController constructor.
   *
   * @param \Drupal\social_api\Plugin\NetworkManager $network_manager
   *   Used to get an instance of social_post_weibo network plugin.
   * @param \Drupal\social_post\SocialPostManager $post_manager
   *   Manages user login/registration.
   * @param \Drupal\social_post_weibo\WeiboPostAuthManager $provider_manager
   *   Used to manage authentication methods.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   Used to access GET parameters.
   * @param \Drupal\social_post\SocialPostDataHandler $data_handler
   *   SocialAuthDataHandler object.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   Used for logging errors.
   */
  public function __construct(NetworkManager $network_manager,
                              SocialPostManager $post_manager,
                              WeiboPostAuthManager $provider_manager,
                              RequestStack $request,
                              SocialPostDataHandler $data_handler,
                              LoggerChannelFactoryInterface $logger_factory) {

    $this->networkManager = $network_manager;
    $this->postManager = $post_manager;
    $this->providerManager = $provider_manager;
    $this->request = $request;
    $this->dataHandler = $data_handler;
    $this->loggerFactory = $logger_factory;

    $this->postManager->setPluginId('social_post_weibo');

    // Sets session prefix for data handler.
    $this->dataHandler->setSessionPrefix('social_post_weibo');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.network.manager'),
      $container->get('social_post.post_manager'),
      $container->get('weibo_post.auth_manager'),
      $container->get('request_stack'),
      $container->get('social_post.data_handler'),
      $container->get('logger.factory')
    );
  }

  /**
   * Redirects user to Weibo for authentication.
   *
   * @return \Zend\Diactoros\Response\RedirectResponse
   *   Redirects to Weibo.
   *
   * @throws \Abraham\WeiboOAuth\WeiboOAuthException
   */
  public function redirectToProvider() {
    /* @var \Drupal\social_post_weibo\Plugin\Network\WeiboPost $network_plugin */
    $network_plugin = $this->networkManager->createInstance('social_post_weibo');

    /* @var \Abraham\WeiboOAuth\WeiboOAuth $connection */
    $connection = $network_plugin->getSdk();

    $url = $connection->getAuthorizeURL($network_plugin->getOauthCallback());

    return new RedirectResponse($url);
  }

  /**
   * Callback function for the authentication process.
   *
   * @throws \Abraham\WeiboOAuth\WeiboOAuthException
   */
  public function callback() {
    // Checks if user denied authorization.
    if ($this->request->getCurrentRequest()->get('denied')) {
      drupal_set_message($this->t('You could not be authenticated.'), 'error');
      return $this->redirect('entity.user.edit_form', ['user' => $this->currentUser->id()]);
    }

    /* @var \Drupal\social_post_weibo\Plugin\Network\WeiboPost $network_plugin */
    $network_plugin = $this->networkManager->createInstance('social_post_weibo');

    /* @var \Abraham\WeiboOAuth\WeiboOAuth $connection */
    $connection = $network_plugin->getSdk();

    // Gets the permanent access token.
    // $access_token = $connection->oauth('oauth/access_token', ['oauth_verifier' => $this->providerManager->getOauthVerifier()]);
    $keys = array();
  	$keys['code'] = $this->request->getCurrentRequest()->get('code');
  	$keys['redirect_uri'] = $network_plugin->getOauthCallback();

    $access_token = $connection->getAccessToken('code', $keys);
    $client = $network_plugin->getSdk2($access_token['access_token']);

    // Gets user information.
    // $params = [
    //   'include_email' => 'true',
    //   'include_entities' => 'false',
    //   'skip_status' => 'true',
    // ];
    //$profile = $connection->get("account/verify_credentials", $params);

    $uid_get = $client->get_uid();
    $uid = $uid_get['uid'];
    $user_message = $client->show_user_by_id($uid);//根据ID获取用户等基本信息

    if (!$this->postManager->checkIfUserExists($uid)) {
      $this->postManager->addRecord($uid, $uid, json_encode($access_token));
      drupal_set_message($this->t('Account added successfully.'), 'status');
    }
    else {
      drupal_set_message($this->t('You have already authorized to post on behalf of this user.'), 'warning');
    }

    return $this->redirect('entity.user.edit_form', ['user' => $this->postManager->getCurrentUser()]);
  }

}
