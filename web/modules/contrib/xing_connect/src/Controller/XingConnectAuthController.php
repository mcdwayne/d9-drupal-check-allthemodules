<?php

namespace Drupal\xing_connect\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Component\Datetime\Time;
use Drupal\Core\Routing\UrlGeneratorInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Drupal\Core\Logger\LoggerChannelFactory;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use Drupal\Core\Session\SessionManager;
use Drupal\Core\Mail\MailManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;

/**
 * Handle the authentication process of xing.
 */
class XingConnectAuthController extends ControllerBase implements ContainerInjectionInterface {
  /**
   * Request stack.
   *
   * @var Symfony\Component\HttpFoundation\RequestStack
   */
  protected $request;

  /**
   * The config_factory variable.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The time variable.
   *
   * @var \Drupal\Component\Datetime\Time
   */
  protected $time;

  /**
   * The url generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The logger factory used for logging.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $loggerFactory;

  /**
   * The session manager used for session management.
   *
   * @var \Drupal\Core\Session\SessionManager
   */
  protected $sessionManager;

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManager
   */
  protected $mailManager;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Class constructor.
   *
   * @param Symfony\Component\HttpFoundation\RequestStack $request
   *   Request stack.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Configuration factory.
   * @param \Drupal\Component\Datetime\Time $time
   *   Time.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $urlGenerator
   *   Url generator.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   Http Client.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $logger_factory
   *   Logger factory.
   * @param \Drupal\Core\Session\SessionManager $sessionManager
   *   Session Manager.
   * @param \Drupal\Core\Mail\MailManager $mailManager
   *   Mail Manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type Manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   Language Manager.
   */
  public function __construct(RequestStack $request, ConfigFactoryInterface $configFactory, Time $time, UrlGeneratorInterface $urlGenerator, ClientInterface $http_client, LoggerChannelFactory $logger_factory, SessionManager $sessionManager, MailManager $mailManager, EntityTypeManagerInterface $entity_type_manager, LanguageManagerInterface $language_manager) {
    $this->request           = $request;
    $this->configFactory     = $configFactory;
    $this->time              = $time;
    $this->urlGenerator      = $urlGenerator;
    $this->http_client       = $http_client;
    $this->loggerFactory     = $logger_factory;
    $this->sessionManager    = $sessionManager;
    $this->mailManager       = $mailManager;
    $this->entityTypeManager = $entity_type_manager;
    $this->languageManager   = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
      // Load the service required to construct this class.
      $container->get('request_stack'),
      $container->get('config.factory'),
      $container->get('datetime.time'),
      $container->get('url_generator'),
      $container->get('http_client'),
      $container->get('logger.factory'),
      $container->get('session_manager'),
      $container->get('plugin.manager.mail'),
      $container->get('entity_type.manager'),
      $container->get('language_manager')
    );
  }

  /**
   * Process the OAuth2 logic to integrate with xing.
   *
   * Request which will cause a client redirect to xing for auth code.
   * On return from that we validate the code and get back a
   * secure token which gives us access to the users Xing
   * account. Then we going and associate the users account
   * and log them in.
   *
   * @see https://dev.xing.com/docs/authentication
   */
  public function xingConnectProcessOauth2() {
    // Session regenerated.
    $this->sessionManager->regenerate();
    $query_string = $this->request->getCurrentRequest()->getQueryString();
    if (!empty($query_string)) {
      parse_str($query_string, $parameters);
    }
    // After Authorization get oauth code.
    if (!empty($parameters)
      && !empty($parameters['oauth_verifier'])
      && !empty($parameters['oauth_token'])
    ) {
      // Check the request made on same time.
      if ($_SESSION['state'] == $parameters['state']) {

        $this->xingConnectAccessToken($parameters['oauth_token'], $parameters['oauth_verifier'], $_SESSION['oauth_token_secret']);
      }
      else {
        // CSRF attack?
        $redirect = new RedirectResponse($this->urlGenerator->generateFromRoute('user.page'));
        return $redirect->send();
      }
    }
    else {
      // Start authorization process.
      $this->xingConnectRequestToken();
    }
  }

  /**
   * Redirect a user to xing to obtain a request Code.
   */
  public function xingConnectRequestToken() {
    // Get Configuration of xing connect.
    $settings = $this->configFactory->getEditable('xing_connect.admin.settings');
    // Session start.
    $this->sessionManager->start();
    $_SESSION['state'] = md5($this->time->getRequestTime());
    // Params options.
    $options = [
      'form_params' => [
        'oauth_signature_method' => 'PLAINTEXT',
        'oauth_version' => '1.0',
        'oauth_signature' => $settings->get('xing_connect_skey') . '&',
        'oauth_timestamp' => $this->time->getRequestTime(),
        'oauth_nonce' => md5(uniqid(mt_rand(), TRUE)),
        'oauth_consumer_key' => $settings->get('xing_connect_ckey'),
        'oauth_callback' => $this->urlGenerator->generateFromRoute(
          'xing_connect.auth',
          [],
          [
            'query' => ['state' => $_SESSION['state']],
            'absolute' => TRUE,
            'https' => FALSE,
          ]
        ),
      ],
    ];
    $url = 'https://api.xing.com/v1/request_token';
    $client = $this->http_client;

    try {
      $response = $client->request('POST', $url, $options);
      // Status of request.
      $status = $response->getStatusCode();
      if ($status == '201') {
        // Getting temparory Token.
        $stream = $response->getBody();
        $stream->rewind();
        $contents = $stream->getContents();
        parse_str($contents, $xing_request_token_response);

        $this->xingConnectAuthorize($xing_request_token_response);
      }
    }
    catch (RequestException $e) {
      $variables = [
        '@message' => 'Could not get request token',
        '@error_message' => $e->getMessage(),
      ];
      $this->loggerFactory->get('xing_connect')
        ->error('@message. Details: @error_message', $variables);

      drupal_set_message($this->t('An error occurred and at this time we are unable to connect to Xing.'), 'error');
      $redirect = new RedirectResponse($this->urlGenerator->generateFromRoute('user.page'));
      return $redirect->send();
    }
  }

  /**
   * Redirect to xing to obtain a Auth Code based on request code.
   *
   * @param array $request_token
   *   The request token value.
   */
  public function xingConnectAuthorize(array $request_token) {
    // Configuration of settings.
    $settings = $this->configFactory->getEditable('xing_connect.admin.settings');
    // Session Start.
    $this->sessionManager->start();
    $_SESSION['state'] = md5($this->time->getRequestTime());
    $_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];

    // Params.
    $options = [
      'query' => [
        'oauth_signature_method' => 'PLAINTEXT',
        'oauth_version' => '1.0',
        'oauth_signature' => $settings->get('xing_connect_skey') . '&',
        'oauth_timestamp' => $this->time->getRequestTime(),
        'oauth_nonce' => md5(uniqid(mt_rand(), TRUE)),
        'oauth_consumer_key' => $settings->get('xing_connect_ckey'),
        'oauth_token' => $request_token['oauth_token'],
        'oauth_callback' => $this->urlGenerator->generateFromRoute(
          'xing_connect.auth',
          [],
          [
            'query' => ['state' => $_SESSION['state']],
            'absolute' => TRUE,
            'https' => FALSE,
          ]
        ),
      ],
    ];
    // Redirect the user to Xing to get an auth code.
    $xing_authorize_url = 'https://api.xing.com/v1/authorize';
    $authorization_endpoint = Url::fromUri($xing_authorize_url, $options)->toString(TRUE);
    $response = new RedirectResponse($authorization_endpoint->getGeneratedUrl(), 302);
    return $response->send();
  }

  /**
   * Redirect a user to xing to obtain a access Code.
   *
   * @param string $oauth_token
   *   The request token value.
   * @param string $oauth_verifier
   *   The request token value.
   * @param string $oauth_token_secret
   *   The request token value.
   *
   * @return array
   *   Return service response.
   */
  public function xingConnectAccessToken($oauth_token, $oauth_verifier, $oauth_token_secret) {
    $settings = $this->configFactory->getEditable('xing_connect.admin.settings');
    // Option of service.
    $options = [
      'form_params' => [
        'oauth_signature_method' => 'PLAINTEXT',
        'oauth_version' => '1.0',
        'oauth_signature' => $settings->get('xing_connect_skey') . '&' . $oauth_token_secret,
        'oauth_timestamp' => $this->time->getRequestTime(),
        'oauth_nonce' => md5(uniqid(mt_rand(), TRUE)),
        'oauth_consumer_key' => $settings->get('xing_connect_ckey'),
        'oauth_token' => $oauth_token,
        'oauth_verifier' => $oauth_verifier,
      ],
    ];

    $url = 'https://api.xing.com/v1/access_token';
    $client = $this->http_client;

    try {
      $response = $client->request('POST', $url, $options);
      // Status of request.
      $status = $response->getStatusCode();
      if ($status == '201') {
        // Response of access token.
        $stream = $response->getBody();
        $stream->rewind();
        $contents = $stream->getContents();
        parse_str($contents, $xing_access_token_response);
        $this->xingConnectauthentication($xing_access_token_response);
      }
    }
    catch (RequestException $e) {
      $variables = [
        '@message' => 'Could not get access token',
        '@error_message' => $e->getMessage(),
      ];
      $this->loggerFactory->get('xing_connect')
        ->error('@message. Details: @error_message', $variables);

      drupal_set_message($this->t('An error occurred and at this time we are unable to connect to Xing.'), 'error');
      $redirect = new RedirectResponse($this->urlGenerator->generateFromRoute('user.page'));
      return $redirect->send();
    }
  }

  /**
   * Handle authenticating Xing account and getting user personal information.
   *
   * @param array $access_token
   *   Xing access token contain oauth_token, oauth_token_secret and user_id.
   *
   * @see https://dev.xing.com/docs/authentication
   * @see http://oauth.net/core/1.0a/
   */
  public function xingConnectauthentication(array $access_token) {
    $settings = $this->configFactory->getEditable('xing_connect.admin.settings');
    if (empty($access_token)) {
      drupal_set_message($this->t('An error occurred and at this time we are unable to connect to Xing.'), 'error');
      $redirect = new RedirectResponse($this->urlGenerator->generateFromRoute('user.page'));
      return $redirect->send();
    }
    $xing_candidate_info = [];
    // Get information from user access token.
    $xing_candidate_info['data'] = $this->getXingCandidateInformation($access_token);
    if (!empty($xing_candidate_info['data']) && !empty($xing_candidate_info['data']->users[0]->active_email)) {
      $account = user_load_by_mail($xing_candidate_info['data']->users[0]->active_email);
      if (!empty($account)) {
        if ($account->status) {
          // Login to site.
          user_login_finalize($account);
          drupal_set_message($this->t('Welcome !!! You have been logged in with the username @username', ['@username' => $account->getUsername()]));
          $redirect = new RedirectResponse($this->urlGenerator->generateFromRoute('user.page'));
          return $redirect->send();
        }
        else {
          drupal_set_message($this->t('You could not be logged in as your account is blocked. Contact site administrator to activate your account.'), 'error');
          $redirect = new RedirectResponse($this->urlGenerator->generateFromRoute('user.page'));
          return $redirect->send();
        }
      }
      else {
        if ($settings->get('xing_connect_login')) {
          // Create the drupal user.
          // This will generate a random password, you could set your own here.
          $xing_username = (isset($xing_candidate_info['data']->users[0]->display_name) ? $xing_candidate_info['data']->users[0]->display_name : $xing_candidate_info['data']->users[0]->first_name);
          $drupal_username_generated = xing_connect_unique_user_name($xing_username);
          $password = user_password(8);

          $langcode = $this->languageManager->getCurrentLanguage()->getId();

          $fields = [
            'name' => $drupal_username_generated,
            'mail' => $xing_candidate_info['data']->users[0]->active_email,
            'init' => $xing_candidate_info['data']->users[0]->active_email,
            'pass' => $password,
            'status' => 1,
            'langcode' => $langcode,
            'preferred_langcode' => $langcode,
            'preferred_admin_langcode' => $langcode,
            'roles' => [
              DRUPAL_AUTHENTICATED_RID => 'authenticated user',
            ],
          ];

          $user = $this->entityTypeManager->getStorage('user')->create($fields);

          // Checking file directory.
          if (!empty($xing_candidate_info['data']->users[0]->photo_urls)
            && !empty($xing_candidate_info['data']->users[0]->photo_urls->size_1024x1024)) {
            $picture_directory = file_default_scheme() . '://pictures/';
            file_prepare_directory($picture_directory, FILE_CREATE_DIRECTORY);
            $file = system_retrieve_file($xing_candidate_info['data']->users[0]->photo_urls->size_1024x1024, $picture_directory . '/' . $xing_candidate_info['data']->users[0]->display_name . '.jpg', TRUE, FILE_EXISTS_RENAME);
            if (is_object($file)) {
              $user->set('user_picture', $file->id());
            }
          }
          $user->save();
          // Login to site.
          user_login_finalize($user);
          // Mail to user.
          $this->mailManager->mail('user', 'register_no_approval_required', $user->mail, NULL, ['account' => $user], NULL, TRUE);
          drupal_set_message($this->t('You have been registered with the username @username', ['@username' => $user->getUsername()]));
          $redirect = new RedirectResponse($this->urlGenerator->generateFromRoute('user.page'));
          return $redirect->send();
        }
        else {
          drupal_set_message($this->t('There was no account with the email addresse @email found. Please register before trying to login.', ['@email' => $xing_candidate_info['data']['users'][0]['active_email']]), 'error');
          $redirect = new RedirectResponse($this->urlGenerator->generateFromRoute('user.page'));
          return $redirect->send();
        }
      }
    }
    else {
      drupal_set_message($this->t('An error occurred while fetching information from Xing.'), 'error');
      $redirect = new RedirectResponse($this->urlGenerator->generateFromRoute('user.page'));
      return $redirect->send();
    }
  }

  /**
   * Here we get the user details from xing.
   *
   * @param string $access_token_parameters
   *   Xing access_token.
   */
  public function getXingCandidateInformation($access_token_parameters) {
    // Configuration of settings.
    $settings = $this->configFactory->getEditable('xing_connect.admin.settings');
    // Fetching field from xing services.
    $xing_fields = 'first_name,last_name,active_email,display_name,photo_urls.size_1024x1024';
    // Params.
    $options = [
      'query' => [
        'fields' => $xing_fields,
        'oauth_consumer_key' => $settings->get('xing_connect_ckey'),
        'oauth_token' => $access_token_parameters['oauth_token'],
        'oauth_signature_method' => 'PLAINTEXT',
        'oauth_version' => '1.0',
        'oauth_signature' => $settings->get('xing_connect_skey') . '&' . $access_token_parameters['oauth_token_secret'],
        'oauth_timestamp' => REQUEST_TIME,
        'oauth_nonce' => md5(uniqid(mt_rand(), TRUE)),
        'format' => 'json',
      ],
    ];
    // Xing search request url.
    $url = 'https://api.xing.com/v1/users' . '/' . $access_token_parameters['user_id'];
    $client = $this->http_client;

    try {
      $response = $client->request('GET', $url, $options);
      // Status of request.
      $status = $response->getStatusCode();
      if ($status == '200') {
        // Getting temparory Token.
        $stream = $response->getBody();
        $stream->rewind();
        $contents = json_decode($stream->getContents());
        return $contents;
      }
    }
    catch (RequestException $e) {
      $variables = [
        '@message' => 'Could not get user Info',
        '@error_message' => $e->getMessage(),
      ];
      $this->loggerFactory->get('xing_connect')
        ->error('@message. Details: @error_message', $variables);

      drupal_set_message($this->t('An error occurred and at this time we are unable to connect to Xing.'), 'error');
      $redirect = new RedirectResponse($this->urlGenerator->generateFromRoute('user.page'));
      return $redirect->send();
    }
  }

}
