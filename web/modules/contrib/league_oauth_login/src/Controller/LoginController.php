<?php

namespace Drupal\league_oauth_login\Controller;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Drupal\externalauth\ExternalAuthInterface;
use Drupal\league_oauth_login\Event\AccessTokenEvent;
use Drupal\league_oauth_login\Event\LoginWhileLoggedInEvent;
use Drupal\league_oauth_login\Event\LoginWithCodeEvent;
use Drupal\league_oauth_login\LeagueOauthLoginEvents;
use Drupal\league_oauth_login\LeagueOauthLoginInterface;
use Drupal\league_oauth_login\LeagueOauthLoginPluginManager;
use Drupal\user\UserDataInterface;
use Drupal\user\UserStorageInterface;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class LoginController.
 *
 * @package Drupal\league_oauth_login\Controller
 */
class LoginController extends ControllerBase {

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;
  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The user data service.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * Event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Session service.
   *
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
   */
  protected $session;

  /**
   * Kill switch.
   *
   * @var \Drupal\Core\PageCache\ResponsePolicy\KillSwitch
   */
  protected $killSwitch;

  /**
   * External auth.
   *
   * @var \Drupal\externalauth\ExternalAuthInterface
   */
  protected $externalAuth;

  /**
   * Current route match.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRoute;

  /**
   * LoginController constructor.
   */
  public function __construct(UserStorageInterface $user_storage, LoggerInterface $logger, ConfigFactoryInterface $config, UserDataInterface $user_data, LeagueOauthLoginPluginManager $login_manager, EventDispatcherInterface $event_dispatcher, SessionInterface $session, KillSwitch $kill_switch, ExternalAuthInterface $external_auth, CurrentRouteMatch $current_route) {
    $this->userStorage = $user_storage;
    $this->logger = $logger;
    $this->configFactory = $config;
    $this->userData = $user_data;
    $this->loginManager = $login_manager;
    $this->eventDispatcher = $event_dispatcher;
    $this->session = $session;
    $this->killSwitch = $kill_switch;
    $this->externalAuth = $external_auth;
    $this->currentRoute = $current_route;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('user'),
      $container->get('logger.factory')->get('league_oauth_login'),
      $container->get('config.factory'),
      $container->get('user.data'),
      $container->get('plugin.manager.league_oauth_login'),
      $container->get('event_dispatcher'),
      $container->get('session'),
      $container->get('page_cache_kill_switch'),
      $container->get('externalauth.externalauth'),
      $container->get('current_route_match')
    );
  }

  /**
   * Helper.
   */
  protected function removeLinking() {
    if ($this->session->get('is_linking')) {
      $this->session->remove('is_linking');
    }
  }

  /**
   * Login.
   */
  public function login(Request $request, $provider_id) {
    $link = FALSE;
    if ($this->session->get('is_linking')) {
      $this->removeLinking();
      $link = TRUE;
    }
    $route_name = $this->currentRoute->getRouteName();
    if ($route_name == 'league_oauth_login.login_controller_link') {
      $link = TRUE;
      $this->session->set('is_linking', TRUE);
    }
    /** @var \Drupal\league_oauth_login\LeagueOauthLoginInterface $plugin */
    try {
      $plugin = $this->loginManager->createInstance($provider_id);
      $config = $plugin->getPluginDefinition();
    }
    catch (PluginNotFoundException $e) {
      $this->removeLinking();
      // Well,let's just return a 404.
      throw new NotFoundHttpException();
    }
    $provider = $plugin->getProvider();
    $account = $this->currentUser();
    if (!$link && $account->isAuthenticated()) {
      $event = new LoginWhileLoggedInEvent($request);
      $this->eventDispatcher->dispatch(LeagueOauthLoginEvents::LOGIN_WHILE_LOGGED_IN, $event);
      $url = Url::fromRoute('user.page')->toString(TRUE)->getGeneratedUrl();
      if ($event->getRedirectUrl()) {
        $url = $event->getRedirectUrl()->toString();
      }
      $response = new TrustedRedirectResponse($url);
      $response->getCacheableMetadata()->setCacheMaxAge(0);
      return $response;
    }
    if ($link && !$account->isAuthenticated()) {
      $this->removeLinking();
      throw new AccessDeniedHttpException();
    }
    if (!$link && !$config['login_enabled']) {
      // Not allowed to login with this provider plugin.
      throw new AccessDeniedHttpException();
    }
    // If we don't have an authorization code then get one.
    if (!$request->get('code')) {
      $options = $plugin->getAuthUrlOptions();
      $authorizationUrl = $provider->getAuthorizationUrl($options);
      // Get the state generated for you and store it to the session.
      $this->session->set('oauth2state', $provider->getState());
      // Redirect the user to the authorization URL.
      $response = new TrustedRedirectResponse($authorizationUrl);
      // Make sure this is not cached.
      $this->killSwitch->trigger();
      $build = [
        '#cache' => [
          'max-age' => 0,
        ],
      ];
      $cache_metadata = CacheableMetadata::createFromRenderArray($build);
      $response->addCacheableDependency($cache_metadata);
      return $response;
    }
    elseif (!$request->get('state') || (!$this->session->get('oauth2state') && $request->get('state') !== $this->session->get('oauth2state'))) {
      // Check given state against previously stored one to mitigate CSRF
      // attack.
      if ($this->session->get('oauth2state')) {
        $this->session->remove('oauth2state');
      }
      $this->removeLinking();
      throw new AccessDeniedHttpException('Illegal state');
    }
    else {
      try {
        // First emit an event, in case anyone wants to do something with this
        // code.
        $event = new LoginWithCodeEvent($request);
        $this->eventDispatcher->dispatch(LeagueOauthLoginEvents::LOGIN_WITH_CODE, $event);
        // Using the access token, we may look up details about the resource
        // owner.
        $access_token = $provider->getAccessToken('authorization_code', [
          'code' => $request->get('code'),
          'grant_type' => 'authorization_code',
          // Some providers need the redirect URI here.
          'redirect_uri' => $this->configFactory->get(sprintf('league_oauth_login_%s.settings', $provider_id))->get('redirectUri'),
        ]);
        // Dispatch an access token event.
        $event = new AccessTokenEvent($request, $access_token);
        $this->eventDispatcher->dispatch(LeagueOauthLoginEvents::ACCESS_TOKEN_EVENT, $event);
        /** @var \League\OAuth2\Client\Provider\ResourceOwnerInterface $resource_owner */
        $resource_owner = $provider->getResourceOwner($access_token);
        $mail = $plugin->getEmail($resource_owner, $access_token);
        $name = $plugin->getUserName($resource_owner);
        $id = $resource_owner->getId();
        if (!$id) {
          // Id should always be set, but in case it is not...
          $id = $name;
        }
        $authname = self::getAuthName($id, $plugin);
        // Make sure the username is not used.
        if ($this->userStorage->loadByProperties(['name' => $name])) {
          $has_valid_name = FALSE;
          $suffix = 2;
          while (!$has_valid_name) {
            $name_suggestion = sprintf('%s_%d', $name, $suffix);
            if (!$this->userStorage->loadByProperties(['name' => $name_suggestion])) {
              $has_valid_name = TRUE;
              $name = $name_suggestion;
            }
            $suffix++;
          }
        }
        if (!$mail) {
          $this->removeLinking();
          throw new \Exception('No email address found');
        }
        $provider_key = self::createUserDataKey($plugin);
        if (!$link) {
          /** @var \Drupal\user\UserInterface $drupal_user */
          $drupal_user = $this->externalAuth->loginRegister($authname, $provider_key, [
            'mail' => $mail,
            'name' => $name,
          ]);
        }
        else {
          // If a user already have another account with this provider, we
          // disallow it.
          if ($existing = $this->externalAuth->load($mail, $provider_key)) {
            $this->removeLinking();
            // Unless it is themself.
            if ($existing->id() == $account->id()) {
              return $this->redirect('user.page');
            }
            throw new AccessDeniedHttpException();
          }
          $drupal_user = $this->userStorage->load($account->id());
          $this->externalAuth->linkExistingAccount($authname, $provider_key, $drupal_user);
        }
        $this->userData->set('league_oauth_login', $drupal_user->id(), $provider_key, $access_token->getToken());
        $this->userData->set('league_oauth_login', $drupal_user->id(), $provider_key . '.serialized', $access_token);
        return $this->redirect('user.page');
      }
      catch (IdentityProviderException $e) {
        $this->removeLinking();
        $this->logger->error('Caught an identity provider exception exception "@e" when trying to do things with login.', [
          '@e' => $e->getMessage(),
        ]);
        return [
          '#cache' => [
            'max-age' => 0,
          ],
          '#markup' => $this->t('There was a problem logging you in.'),
        ];
      }
      catch (\Throwable $e) {
        $this->removeLinking();
        $this->logger->error('Caught exception "@e" when trying to do things with login.', [
          '@e' => $e->getMessage(),
        ]);
        return [
          '#cache' => [
            'max-age' => 0,
          ],
          '#markup' => $this->t('There was a problem logging you in.'),
        ];
      }
    }
  }

  /**
   * Create a key to use in the user data storage.
   */
  public static function createUserDataKey(LeagueOauthLoginInterface $plugin) {
    return sprintf('%s.token', $plugin->getPluginId());
  }

  /**
   * Helper.
   */
  public static function getAuthName($id, LeagueOauthLoginInterface $plugin) {
    return sprintf('%s.%s', $id, $plugin->getPluginId());;
  }

}
