<?php

namespace Drupal\openid_connect_rest\Controller;

use Exception;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\Core\Url;
use Drupal\Core\Database\Connection;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

use Drupal\Component\Uuid\Php;

use GuzzleHttp\ClientInterface;

use Drupal\openid_connect\Claims;
use Drupal\openid_connect\Authmap;
use Drupal\openid_connect\Controller\RedirectController;

use Drupal\openid_connect_rest\Entity\StateToken;
use Drupal\openid_connect_rest\Entity\AuthorizationMapping;
use Drupal\openid_connect_rest\Plugin\OpenIDConnectRESTClientManager;

/**
 * Class ApiController.
 *
 * @package Drupal\openid_connect_rest\Controller
 */
class ApiController extends ControllerBase {

  /**
   * OpenIDConnectRESTClientManager definition.
   *
   * @var \Drupal\openid_connect_rest\Plugin\OpenIDConnectRESTClientManager
   */
  protected $pluginManager;

  /**
   * The request stack used to access request globals.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Drupal\Core\Session\AccountProxy definition.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Provides language support.
   *
   * @var \Drupal\Core\Language\LanguageManager
   */
  protected $languageManager;

  /**
   * The Base Database API.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The Entity API.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The uuid utility.
   *
   * @var \Drupal\Component\Uuid\Php
   */
  protected $uuid;

  /**
   * The OpenID Connect claims.
   *
   * @var \Drupal\openid_connect\Claims
   */
  protected $claims;

  /**
   * The OpenID Authorization mapping service.
   *
   * @var \Drupal\openid_connect\Authmap
   */
  protected $authmap;

  /**
   * The OpenID Connect redirect controller.
   *
   * @var \Drupal\openid_connect\Controller\RedirectController
   */
  protected $redirectController;

  /**
   * {@inheritdoc}
   *
   * The constructor.
   *
   * @param \Drupal\openid_connect_rest\Plugin\OpenIDConnectRESTClientManager $plugin_manager
   *   The plugin manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack used to access request globals.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client to fetch the feed data with.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Drupal\Core\Session\AccountProxy definition.
   * @param \Drupal\Core\Language\LanguageManager $language_manager
   *   Provides language support.
   * @param \Drupal\Core\Database\Connection $database
   *   The Base Database API.
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The Entity API.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\Component\Uuid\Php $uuid
   *   The uuid utility.
   * @param \Drupal\openid_connect\Claims $claims
   *   The OpenID Connect claims.
   * @param \Drupal\openid_connect\Authmap $authmap
   *   The OpenID Authorization mapping service.
   * @param \Drupal\openid_connect\Controller\RedirectController $redirect_controller
   *   The OpenID Connect redirect controller.
   */
  public function __construct(
      OpenIDConnectRESTClientManager $plugin_manager,
      RequestStack $request_stack,
      ClientInterface $http_client,
      AccountInterface $current_user,
      LanguageManager $language_manager,
      Connection $database,
      EntityTypeManager $entity_type_manager,
      LoggerChannelFactoryInterface $logger_factory,
      Php $uuid,
      Claims $claims,
      Authmap $authmap,
      RedirectController $redirect_controller
  ) {

    $this->pluginManager = $plugin_manager;

    $this->requestStack = $request_stack;
    $this->httpClient = $http_client;
    $this->currentUser = $current_user;
    $this->languageManager = $language_manager;
    $this->database = $database;
    $this->entityTypeManager = $entity_type_manager;

    $this->loggerFactory = $logger_factory;
    $this->uuid = $uuid;

    $this->claims = $claims;
    $this->authmap = $authmap;
    $this->redirectController = $redirect_controller;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.openid_connect_rest_client.processor'),
      $container->get('request_stack'),
      $container->get('http_client'),
      $container->get('current_user'),
      $container->get('language_manager'),
      $container->get('database'),
      $container->get('entity_type.manager'),
      $container->get('logger.factory'),
      $container->get('uuid'),
      $container->get('openid_connect.claims'),
      $container->get('openid_connect.authmap'),
      $container->get('openid_connect_rest.openid_connect.redirect_controller')
    );
  }

  /**
   * Access callback for ApiController::getToken().
   *
   * @return bool
   *   Whether the authorization has a match in the authorization mappings.
   */
  public function canAccessGetToken() {
    $currentRequest = $this->requestStack->getCurrentRequest();

    $state_token = $currentRequest->request->get('state');
    $authorization_code = $currentRequest->request->get('code');
    $authorization_mapping = $this->findAuthorizationMapping($authorization_code, $state_token);
    if (!empty($authorization_mapping) && is_object($authorization_mapping)) {
      if ($this->userSubIsValid($authorization_mapping)) {
        return AccessResult::allowed();
      }
      $authorization_mapping->delete();
    }

    return AccessResult::forbidden();
  }

  /**
   * Access callback for for ApiController::authenticate().
   *
   * @return bool
   *   Whether the authorization has a match in the authorization mappings.
   */
  public function canAccessAuthenticate() {
    if ($this->redirectController->access()->isAllowed()) {
      return AccessResult::allowed();
    }
    else {
      $currentRequest = $this->requestStack->getCurrentRequest();

      $state_token = $currentRequest->query->get('state');
      $state_token = $this->findStateToken($state_token);
      if (!empty($state_token) && is_object($state_token)) {
        if ($state_token->expires >= ((new DrupalDateTime())->format('U'))) {
          return AccessResult::allowed();
        }
        $state_token->delete();
      }
      return AccessResult::forbidden();
    }
  }

  /**
   * Display success of failure page.
   *
   * @param string $client_name
   *   The provider id.
   *
   * @return array
   *   The renderable array.
   */
  public function authenticate($client_name) {
    $provider_id = $client_name;

    $authorized = FALSE;
    $user = $this->currentUser;
    if (!empty($provider_id)) {
      if ($user->isAuthenticated()) {
        if ($this->isValidProviderId($provider_id)) {
          $user_sub = $this->findUserSub($user);
          if (!empty($user_sub)) {
            $authorized = openid_connect_rest_openid_connect_post_authorize([], $user, [
              'sub' => $user_sub,
            ], $provider_id);
          }
        }
      }
      else {
        $this->redirectController->authenticate($provider_id);
        $authorized = TRUE;
      }
    }

    // State token can be safely deleted from database.
    $currentRequest = $this->requestStack->getCurrentRequest();
    $state_token = $currentRequest->query->get('state');
    $this->deleteStateToken($state_token);

    if ($authorized && $user->isAuthenticated()) {
      $authenticated = TRUE;
      $message = $this->t('You have been successfully authenticated by @provider_id', [
        '@provider_id' => $provider_id,
      ]);
    }
    else {
      $authenticated = FALSE;
      $message = $this->t('We could not authenticate you with @provider_id', [
        '@provider_id' => $provider_id,
      ]);
    }

    return [
      '#message' => $message,
      '#authenticated' => $authenticated,
      '#theme' => 'openid_connect_rest_authenticate_page',
    ];
  }

  /**
   * Oauth token endpoint.
   *
   * @param string $provider_id
   *   The provider id.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The json response with the authentication token.
   */
  public function getToken($provider_id) {
    $json_response = [
      'error' => 'OpenID Connect REST API error',
      'message' => $this->t('Sorry, something went wrong.'),
    ];

    $configuration = $this->config('openid_connect.settings.' . $provider_id);
    if (!empty($configuration)) {
      $settings = $configuration->get('settings');
      if (!empty($settings)) {
        $provider = $this->pluginManager->createInstance(
          $provider_id,
          $settings
        );
      }
    }

    if (!empty($provider)) {
      $currentRequest = $this->requestStack->getCurrentRequest();

      $state_token = $currentRequest->request->get('state');
      $authorization_code = $currentRequest->request->get('code');
      $authorization_mapping = $this->findAuthorizationMapping($authorization_code, $state_token);

      if (!empty($authorization_mapping) && is_object($authorization_mapping)) {
        if ($this->userSubIsValid($authorization_mapping)) {
          $sub = $authorization_mapping->user_sub;
          $account = $this->authmap->userLoadBySub($sub, $provider->getPluginId());
          if ($account) {
            $hashed_password = $account->getPassword();

            if (!empty($hashed_password)) {
              // Set a new temporary user password.
              $account->setPassword($sub);
              $account->save();
              $json_response = $this->getBearerFromOauth($account->getUsername(), $sub);
              // Restore the old password.
              $this->setHashedUserPassword($account->id(), $hashed_password);
            }
          }
        }
        $authorization_mapping->delete();
      }
    }

    return new JsonResponse($json_response);
  }

  /**
   * Provider ids endpoint.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The json response with the available provider ids.
   */
  public function getProviderIds() {
    $definitions = $this->pluginManager->getDefinitions();
    $json_response = [];
    foreach ($definitions as $provider_id => $provider) {
      if (!$this->config('openid_connect.settings.' . $provider_id)
        ->get('enabled')) {
        continue;
      }

      $json_response[$provider_id] = $this->t('Log in with @provider_label', [
        '@provider_label' => $provider['label'],
      ]);
    }
    return new JsonResponse($json_response);
  }

  /**
   * Provider authorization URL endpoint.
   *
   * @param string $provider_id
   *   The provider id.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The json response with the provider authorization endpoint URL.
   */
  public function getAuthorizationEndpoint($provider_id) {
    $json_response = [];
    if ($this->isValidProviderId($provider_id)) {
      openid_connect_save_destination();
      $configuration = $this->config('openid_connect.settings.' . $provider_id)
        ->get('settings');
      $provider = $this->pluginManager->createInstance(
        $provider_id,
        $configuration
      );

      $scopes = $this->claims->getScopes();
      $_SESSION['openid_connect_op'] = 'login';
      $response = $provider->authorize($scopes);

      $language_none = $this->languageManager
        ->getLanguage(LanguageInterface::LANGCODE_NOT_APPLICABLE);

      $redirect_ctrl_internal_path = (Url::fromRoute('openid_connect.redirect_controller_redirect', [
        'client_name' => $provider_id,
      ], [
        'absolute' => FALSE,
        'language' => $language_none,
      ]))->getInternalPath();

      $rest_api_ctrl_internal_path = (Url::fromRoute('openid_connect_rest.api.authenticate', [
        'client_name' => $provider_id,
      ], [
        'absolute' => FALSE,
        'language' => $language_none,
      ]))->getInternalPath();

      $target_url = $response->getTargetUrl();

      $target_url = str_replace(
        $redirect_ctrl_internal_path,
        $rest_api_ctrl_internal_path,
        $target_url
      );

      $components = $this->getUriComponents($target_url);

      if (!empty($components['parameters']['state'])) {
        if ($this->storeStateToken($components['parameters']['state'])) {
          $json_response['target_url'] = $target_url;
          $json_response['components'] = $components;
        }
        else {
          $json_response = [
            'error' => 'OpenID Connect REST API error',
            'message' => $this->t('Unable to create state token.'),
          ];
        }
      }
      else {
        $json_response = [
          'error' => 'OpenID Connect REST API error',
          'message' => $this->t('Invalid state token.'),
        ];
      }
    }
    else {
      $json_response = [
        'error' => 'OpenID Connect REST API error',
        'message' => $this->t('Invalid provider id.'),
      ];
    }

    return new JsonResponse($json_response);
  }

  /**
   * Gets the uri components from a valid uri.
   *
   * @param string $uri
   *   The uri.
   *
   * @return array
   *   The uri components.
   */
  private function getUriComponents($uri) {
    $components = [
      'base_url' => NULL,
      'parameters' => NULL,
    ];
    if (!empty($uri)) {
      $uri = explode('?', $uri, 2);
      if (count($uri)) {
        if (!empty($uri[0])) {
          $components['base_url'] = $uri[0];
        }

        if (!empty($uri[1])) {
          $components['parameters'] = [];
          parse_str($uri[1], $components['parameters']);
        }
      }
    }
    return $components;
  }

  /**
   * Gets the token from Oauth2.
   *
   * @param string $username
   *   The user username.
   * @param string $password
   *   The user password.
   *
   * @return array
   *   The Oauth2 returned data or an array describing the error.
   */
  private function getBearerFromOauth($username, $password) {
    $currentRequest = $this->requestStack->getCurrentRequest();

    $client_id = $currentRequest->request->get('client_id');
    $client_secret = $currentRequest->request->get('client_secret');
    if (!empty($username) && !empty($password) && !empty($client_id) && !empty($client_secret)) {
      $request_options = [
        'form_params' => [
          'grant_type' => 'password',
          'client_id' => $client_id,
          'client_secret' => $client_secret,
          'username' => $username,
          'password' => $password,
        ],
        'http_errors' => FALSE,
        'allow_redirects' => FALSE,
      ];

      /* @var \GuzzleHttp\ClientInterface $client */
      $client = $this->httpClient;
      try {
        $language_none = $this->languageManager
          ->getLanguage(LanguageInterface::LANGCODE_NOT_APPLICABLE);
        $raw_response = $client->request('POST', (Url::fromRoute('oauth2_token.token', [], [
          'absolute' => TRUE,
          'language' => $language_none,
        ]))->toString(), $request_options);
        $json_response = json_decode((string) $raw_response->getBody(), TRUE);
        return $json_response;
      }
      catch (Exception $e) {
        return [
          'error' => 'OpenID Connect REST API error',
          'message' => $e->getMessage(),
        ];
      }
      return [
        'error' => 'OpenID Connect REST API error',
        'message' => $this->t('Could not get a valid token from OAuth service. Missing some mandatory data.'),
      ];
    }
    return [
      'error' => 'OpenID Connect REST API error',
      'message' => $this->t('Could not get a valid token from OAuth service.'),
    ];
  }

  /**
   * Sets the a user already hashed password.
   *
   * @param string $user_id
   *   The user id.
   * @param string $hashed_password
   *   The user hashed password.
   *
   * @return bool
   *   Whether the update has succeeded or not.
   */
  private function setHashedUserPassword($user_id, $hashed_password) {
    if (!empty($user_id) && !empty($hashed_password)) {
      $updated = $this->database->update('users_field_data')
        ->condition('uid', $user_id)
        ->fields([
          'pass' => $hashed_password,
        ])
        ->execute();
      return $updated;
    }
    return FALSE;
  }

  /**
   * Checks if a provider is valid.
   *
   * @param string $provider_id
   *   The provider id.
   *
   * @return bool
   *   Whether the provider is valid or not.
   */
  private function isValidProviderId($provider_id) {
    $definitions = $this->pluginManager->getDefinitions();
    if (!empty($provider_id) && !empty($definitions[$provider_id])) {
      if ($this->config('openid_connect.settings.' . $provider_id)
        ->get('enabled')) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Checks if a user sub is valid.
   *
   * @param \Drupal\openid_connect_rest\Entity\AuthorizationMapping $authorization_mapping
   *   An authorization mapping.
   *
   * @return bool
   *   Whether the user sub is valid or not.
   */
  private function userSubIsValid(AuthorizationMapping $authorization_mapping) {
    if (!empty($authorization_mapping) && is_object($authorization_mapping)) {
      if (!empty($authorization_mapping->expires) && !empty($authorization_mapping->user_sub)) {
        if ($authorization_mapping->expires >= ((new DrupalDateTime())->format('U'))) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

  /**
   * Finds a user sub.
   *
   * @param \Drupal\Core\Session\AccountProxy $user
   *   A fully qualified user.
   *
   * @return mixed
   *   A user sub or null.
   */
  private function findUserSub(AccountProxy $user) {
    if ($user->id()) {
      $records = $this->database->select('openid_connect_authmap', 'a')
        ->fields('a', [
          'sub',
        ])
        ->condition('uid', $user->id())
        ->execute();
      if (!empty($records)) {
        $record = $records->fetchAssoc();
        if (!empty($record) && !empty($record['sub'])) {
          return $record['sub'];
        }
      }
    }
    return NULL;
  }

  /**
   * Finds a state token.
   *
   * @param string $state_token
   *   An OpenID Connect state token.
   *
   * @return mixed
   *   A state token or null.
   */
  private function findStateToken($state_token) {
    if (!empty($state_token)) {
      $storage = $this->entityTypeManager->getStorage('state_token');
      $state_token_ids = $storage->getQuery()
        ->condition('state_token', $state_token)
        ->range(0, 1)
        ->execute();
      if (!empty($state_token_ids)) {
        $state_tokens = $storage->loadMultiple($state_token_ids);
        if (!empty($state_tokens) && is_array($state_tokens)) {
          $state_token = current($state_tokens);
          if (!empty($state_token)) {
            return $state_token;
          }
        }
      }
    }
    return NULL;
  }

  /**
   * Insert or updates a state token.
   *
   * @param string $new_state_token
   *   An OpenID Connect state token.
   *
   * @return bool
   *   Whether to state token is stored or not.
   */
  private function storeStateToken($new_state_token) {
    $state_token = $this->findStateToken($new_state_token);
    if ($state_token) {
      $state_token->expires = ((new DrupalDateTime())->format('U')) + 1800;
    }
    else {
      $state_token = StateToken::create([
        'id' => $this->uuid->generate(),
        'state_token' => $new_state_token,
        'expires' => ((new DrupalDateTime())->format('U')) + 1800,
      ]);
    }

    try {
      $state_token->save();
      return TRUE;
    }
    catch (Exception $e) {
      return FALSE;
    }
  }

  /**
   * Finds an authorization mapping.
   *
   * @param string $authorization_code
   *   A provider authorization code.
   * @param string $state_token
   *   An OpenID Connect state token.
   *
   * @return mixed
   *   An authorization mapping or null.
   */
  private function findAuthorizationMapping($authorization_code, $state_token) {
    if (!empty($authorization_code) && !empty($state_token)) {
      $storage = $this->entityTypeManager->getStorage('authorization_mapping');
      $authorization_mapping_ids = $storage->getQuery()
        ->condition('authorization_code', $authorization_code)
        ->condition('state_token', $state_token)
        ->range(0, 1)
        ->execute();
      if (!empty($authorization_mapping_ids)) {
        $authorization_mappings = $storage->loadMultiple($authorization_mapping_ids);
        if (!empty($authorization_mappings) && is_array($authorization_mappings)) {
          $authorization_mapping = current($authorization_mappings);
          if (!empty($authorization_mapping)) {
            return $authorization_mapping;
          }
        }
      }
    }
    return NULL;
  }

  /**
   * Deletes a state token.
   *
   * @param string $state_token
   *   A state token.
   */
  private function deleteStateToken($state_token) {
    if (!empty($state_token)) {
      $state_token = $this->findStateToken($state_token);
      if (!empty($state_token) && is_object($state_token)) {
        $state_token->delete();
      }
    }
  }

}
