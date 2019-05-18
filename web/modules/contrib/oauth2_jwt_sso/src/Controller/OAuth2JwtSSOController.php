<?php

namespace Drupal\oauth2_jwt_sso\Controller;

use Lcobucci\JWT\Parser;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\oauth2_jwt_sso\Authentication\Provider\OAuth2JwtSSOProvider;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class OAuth2JwtSSOController extends ControllerBase implements ContainerInjectionInterface{

  /**
   * The configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
   */
  protected $session;

  /**
   * Constructs a OAuth2JwtSSOController object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory, SessionInterface $session) {
    $this->configFactory = $config_factory;
    $this->session = $session;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('session')
    );
  }

  function authcodeLogin(Request $request) {
    $remote_login_roles = $this->configFactory
      ->get('oauth2_jwt_sso.settings')
      ->get('roles_remote_login');
    $provider = new OAuth2JwtSSOProvider($this->configFactory, $request->getSession(), [
      'redirectUri' => $GLOBALS['base_url'] . '/user/login/remote',
      'scope' => implode(' ', $remote_login_roles),
    ]);
    $code = $request->get('code');
    $state = $request->get('state');
    if ($code == NULL) {
      $authorizationUrl = $provider->getAuthorizationUrl();
      $_SESSION['oauth2state'] = $provider->getState();
      $response = TrustedRedirectResponse::create($authorizationUrl);

      return $response;
    }
    elseif (empty($state) || (isset($_SESSION['oauth2state']) && $state !== $_SESSION['oauth2state'])) {
      if (isset($_SESSION['oauth2state'])) {
        unset($_SESSION['oauth2state']);
      }
      throw new AccessDeniedHttpException('Invalid State');
    }
    else {
        $accessToken = $provider->getAccessToken('authorization_code', ['code' => $code]);
        $token = (new Parser())->parse($accessToken->getToken());
        if ($provider->verifyToken($token) && $user = $provider->tokenAuthUser($token)) {
          user_login_finalize($user);
          $this->session->set('sso-token', $accessToken->getToken());
          return $this->redirect('<front>');
        }
        else {
          throw new AccessDeniedHttpException('Invalid Token');
        }
    }
  }

  function logout(){
    $authserver_logout_url = $this->configFactory
      ->get('oauth2_jwt_sso.settings')
      ->get('logout_url');
    user_logout();
    return ($authserver_logout_url) ? new TrustedRedirectResponse($authserver_logout_url) : $this->redirect('<front>');
  }

}
